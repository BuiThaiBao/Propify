<?php

namespace App\Repositories\Eloquent;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Repositories\ChatRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentChatRepository implements ChatRepository
{
    public function __construct(
        protected readonly Conversation $conversationModel,
        protected readonly Message $messageModel,
        protected readonly ConversationParticipant $participantModel,
    ) {
    }

    /**
     * Tìm conversation theo normalized pair (min/max user IDs).
     * Tránh duplicate: participant_a_id luôn < participant_b_id.
     */
    public function findConversation(int $userAId, int $userBId, ?int $listingId): ?Conversation
    {
        [$minId, $maxId] = $this->normalizeIds($userAId, $userBId);

        return $this->conversationModel
            ->where('participant_a_id', $minId)
            ->where('participant_b_id', $maxId)
            ->where('listing_id', $listingId) // null-safe: null === null
            ->first();
    }

    /**
     * Tạo conversation mới + seed 2 participants.
     */
    public function createConversation(int $userAId, int $userBId, ?int $listingId): Conversation
    {
        [$minId, $maxId] = $this->normalizeIds($userAId, $userBId);

        return DB::transaction(function () use ($minId, $maxId, $listingId) {
            $conversation = $this->conversationModel->create([
                'participant_a_id' => $minId,
                'participant_b_id' => $maxId,
                'listing_id'       => $listingId,
            ]);

            // Seed participants
            $this->participantModel->create([
                'conversation_id' => $conversation->id,
                'user_id'         => $minId,
            ]);
            $this->participantModel->create([
                'conversation_id' => $conversation->id,
                'user_id'         => $maxId,
            ]);

            return $conversation;
        });
    }

    public function getConversationsForUser(int $userId): Collection
    {
        return $this->conversationModel
            ->where('participant_a_id', $userId)
            ->orWhere('participant_b_id', $userId)
            ->with([
                'participantA:id,full_name,avatar_url',
                'participantB:id,full_name,avatar_url',
                'latestMessage',
                'conversationParticipants' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                },
            ])
            ->latest('updated_at') // Conversation được touch() khi có message mới
            ->get();
    }

    public function getMessages(int $conversationId, ?string $cursor, int $perPage = 20): CursorPaginator
    {
        return $this->messageModel
            ->where('conversation_id', $conversationId)
            ->where('is_deleted', false)
            ->with('sender:id,full_name,avatar_url')
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    /**
     * Kiểm tra conversation tồn tại và user là participant — 1 query duy nhất.
     */
    public function conversationBelongsToUser(int $conversationId, int $userId): ?Conversation
    {
        return $this->conversationModel
            ->where('id', $conversationId)
            ->where(function ($q) use ($userId) {
                $q->where('participant_a_id', $userId)
                  ->orWhere('participant_b_id', $userId);
            })
            ->first();
    }

    public function createMessage(array $data): Message
    {
        $message = $this->messageModel->create($data);

        // Touch conversation updated_at → dùng để sort conversations (mới nhất lên đầu)
        $this->conversationModel
            ->where('id', $data['conversation_id'])
            ->update(['updated_at' => now()]);

        return $message;
    }

    public function isParticipant(int $conversationId, int $userId): bool
    {
        return $this->participantModel
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function markAsRead(int $conversationId, int $userId): void
    {
        $this->participantModel
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }

    public function findById(int $conversationId): ?Conversation
    {
        return $this->conversationModel
            ->with([
                'participantA:id,full_name,avatar_url',
                'participantB:id,full_name,avatar_url',
            ])
            ->find($conversationId);
    }

    public function countUnread(int $conversationId, int $userId): int
    {
        $participant = $this->participantModel
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if (!$participant) {
            return 0;
        }

        $query = $this->messageModel
            ->where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where('is_deleted', false);

        if ($participant->last_read_at) {
            $query->where('created_at', '>', $participant->last_read_at);
        }

        return $query->count();
    }

    public function updateLastSeen(int $conversationId, int $userId): void
    {
        $this->participantModel
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update(['last_seen_at' => now()]);
    }

    // ==================== Private helpers ====================

    /**
     * Normalize user ID pair: [min, max].
     * Đảm bảo participant_a_id < participant_b_id luôn luôn.
     *
     * @return array{int, int}
     */
    private function normalizeIds(int $userAId, int $userBId): array
    {
        return [min($userAId, $userBId), max($userAId, $userBId)];
    }
}
