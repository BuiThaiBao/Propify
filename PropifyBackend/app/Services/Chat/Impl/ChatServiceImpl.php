<?php

namespace App\Services\Chat\Impl;

use App\DTOs\Chat\GetOrCreateConversationDto;
use App\DTOs\Chat\SendMessageDto;
use App\Events\Chat\MessageSent;
use App\Exceptions\ConversationNotFoundException;
use App\Exceptions\UnauthorizedConversationAccessException;
use App\Models\Conversation;
use App\Models\Message;
use App\Repositories\ChatRepository;
use App\Services\Chat\ChatService;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;

final class ChatServiceImpl implements ChatService
{
    public function __construct(
        private readonly ChatRepository $chatRepository,
    ) {
    }

    /**
     * Lấy hoặc tạo conversation — không bao giờ tạo duplicate nhờ:
     * 1. normalized IDs (min/max)
     * 2. DB unique constraint
     * 3. firstOrCreate pattern trong repository
     */
    public function getOrCreateConversation(GetOrCreateConversationDto $dto): Conversation
    {
        $existing = $this->chatRepository->findConversation(
            $dto->currentUserId,
            $dto->otherUserId,
            $dto->listingId,
        );

        if ($existing) {
            return $existing->loadMissing([
                'participantA:id,full_name,avatar_url',
                'participantB:id,full_name,avatar_url',
            ]);
        }

        return $this->chatRepository->createConversation(
            $dto->currentUserId,
            $dto->otherUserId,
            $dto->listingId,
        );
    }

    public function getConversations(int $userId): Collection
    {
        return $this->chatRepository->getConversationsForUser($userId);
    }

    /**
     * Cursor pagination cho messages — infinite scroll từ dưới lên.
     * Throw 403 nếu user không phải participant.
     */
    public function getMessages(int $conversationId, int $userId, ?string $cursor): CursorPaginator
    {
        $this->assertParticipant($conversationId, $userId);

        // Update last_seen khi user vào xem messages
        $this->chatRepository->updateLastSeen($conversationId, $userId);

        return $this->chatRepository->getMessages($conversationId, $cursor);
    }

    /**
     * Gửi message và broadcast qua Redis → Reverb → client.
     * ShouldBroadcast sử dụng queue → không block response.
     */
    public function sendMessage(SendMessageDto $dto): Message
    {
        $this->assertParticipant($dto->conversationId, $dto->senderId);

        $message = $this->chatRepository->createMessage([
            'conversation_id' => $dto->conversationId,
            'sender_id'       => $dto->senderId,
            'type'            => $dto->type->value,
            'body'            => $dto->body,
            'file_url'        => $dto->fileUrl,
        ]);

        // Dispatch event vào queue → broadcast không đồng bộ (không block request)
        MessageSent::dispatch($message);

        return $message->loadMissing('sender:id,full_name,avatar_url');
    }

    public function markAsRead(int $conversationId, int $userId): void
    {
        $this->assertParticipant($conversationId, $userId);
        $this->chatRepository->markAsRead($conversationId, $userId);
    }

    // ==================== Private Guard ====================

    /**
     * Kiểm tra user có phải participant không. Throw 403 nếu không.
     */
    private function assertParticipant(int $conversationId, int $userId): void
    {
        $conversation = $this->chatRepository->findById($conversationId);

        if (!$conversation) {
            throw new ConversationNotFoundException();
        }

        if (!$this->chatRepository->isParticipant($conversationId, $userId)) {
            throw new UnauthorizedConversationAccessException();
        }
    }
}
