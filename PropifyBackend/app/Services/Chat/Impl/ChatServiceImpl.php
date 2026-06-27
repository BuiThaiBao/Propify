<?php

namespace App\Services\Chat\Impl;

use App\DTOs\Chat\CreateGroupDto;
use App\DTOs\Chat\GetOrCreateConversationDto;
use App\DTOs\Chat\GroupMemberDto;
use App\DTOs\Chat\SendMessageDto;
use App\DTOs\Chat\UpdateGroupDto;
use App\Enums\ConversationRole;
use App\Enums\ErrorCode;
use App\Enums\MessageType;
use App\Enums\SystemMessageAction;
use App\Events\Chat\MessageSent;
use App\Exceptions\BusinessException;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Repositories\ChatRepository;
use App\Services\Chat\ChatService;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;

final class ChatServiceImpl implements ChatService
{
    public function __construct(
        private readonly ChatRepository $chatRepository,
    ) {}

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

    public function getMessages(int $conversationId, int $userId, ?string $cursor): CursorPaginator
    {
        $this->assertParticipant($conversationId, $userId);
        $this->chatRepository->updateLastSeen($conversationId, $userId);

        return $this->chatRepository->getMessages($conversationId, $cursor);
    }

    public function sendMessage(SendMessageDto $dto): Message
    {
        $this->assertParticipant($dto->conversationId, $dto->senderId);

        $message = $this->chatRepository->createMessage([
            'conversation_id' => $dto->conversationId,
            'sender_id' => $dto->senderId,
            'type' => $dto->type->value,
            'body' => $dto->body,
            'file_url' => $dto->fileUrl,
            'metadata' => $dto->metadata,
        ]);

        try {
            MessageSent::dispatch($message);
        } catch (\Throwable $e) {
            logger()->error('Failed to broadcast MessageSent: '.$e->getMessage(), [
                'exception' => $e,
                'message_id' => $message->id,
            ]);
        }

        return $message->loadMissing('sender:id,full_name,avatar_url');
    }

    public function markAsRead(int $conversationId, int $userId): void
    {
        $this->assertParticipant($conversationId, $userId);
        $this->chatRepository->markAsRead($conversationId, $userId);
    }

    public function createGroup(CreateGroupDto $dto): Conversation
    {
        $memberIds = array_values(array_unique(array_filter(
            $dto->memberIds,
            static fn (int $id) => $id !== $dto->creatorId,
        )));

        if ((count($memberIds) + 1) > 50) {
            throw new BusinessException(ErrorCode::GroupMemberLimitReached);
        }

        $conversation = $this->chatRepository->createGroupConversation(
            $dto->creatorId,
            $dto->name,
            $dto->avatarUrl,
        );

        if ($memberIds !== []) {
            $this->chatRepository->addParticipants($conversation->id, $memberIds);
        }

        $this->createSystemMessage($conversation->id, $dto->creatorId, SystemMessageAction::GroupCreated);

        return $this->chatRepository->findById($conversation->id);
    }

    public function updateGroup(UpdateGroupDto $dto): Conversation
    {
        $this->assertGroupAdmin($dto->conversationId, $dto->userId);
        $existing = $this->chatRepository->findById($dto->conversationId);

        $payload = [];
        if ($dto->name !== null && $dto->name !== $existing->name) {
            $payload['name'] = $dto->name;
        }
        if ($dto->avatarUrl !== null && $dto->avatarUrl !== $existing->avatar_url) {
            $payload['avatar_url'] = $dto->avatarUrl;
        }

        if ($payload === []) {
            return $existing;
        }

        $updated = $this->chatRepository->updateConversation($dto->conversationId, $payload);

        if (array_key_exists('name', $payload)) {
            $this->createSystemMessage(
                $dto->conversationId,
                $dto->userId,
                SystemMessageAction::GroupRenamed,
                ['new_name' => $payload['name']],
            );
        }

        if (array_key_exists('avatar_url', $payload)) {
            $this->createSystemMessage($dto->conversationId, $dto->userId, SystemMessageAction::AvatarChanged);
        }

        return $updated;
    }

    public function addGroupMembers(GroupMemberDto $dto): Conversation
    {
        $this->assertGroupAdmin($dto->conversationId, $dto->actorId);
        $currentCount = $this->chatRepository->countParticipants($dto->conversationId);

        if (($currentCount + count($dto->userIds)) > 50) {
            throw new BusinessException(ErrorCode::GroupMemberLimitReached);
        }

        $existingIds = $this->chatRepository->getParticipants($dto->conversationId)
            ->pluck('user_id')
            ->all();
        $newIds = array_values(array_diff($dto->userIds, $existingIds));

        if ($newIds === []) {
            throw new BusinessException(ErrorCode::AlreadyGroupMember);
        }

        $this->chatRepository->addParticipants($dto->conversationId, $newIds);

        foreach (User::whereIn('id', $newIds)->get(['id', 'full_name']) as $user) {
            $this->createSystemMessage(
                $dto->conversationId,
                $dto->actorId,
                SystemMessageAction::MemberAdded,
                ['target_id' => $user->id, 'target_name' => $user->full_name],
            );
        }

        return $this->chatRepository->findById($dto->conversationId);
    }

    public function removeGroupMember(int $conversationId, int $actorId, int $userId): Conversation
    {
        $this->assertGroupAdmin($conversationId, $actorId);

        if ($actorId === $userId) {
            throw new BusinessException(ErrorCode::CannotRemoveSelf);
        }

        $participant = $this->chatRepository->getParticipant($conversationId, $userId);
        if (! $participant) {
            throw new BusinessException(ErrorCode::NotGroupMember);
        }

        $this->chatRepository->removeParticipant($conversationId, $userId);

        $target = User::find($userId);
        $this->createSystemMessage(
            $conversationId,
            $actorId,
            SystemMessageAction::MemberRemoved,
            ['target_id' => $userId, 'target_name' => $target?->full_name ?? 'Ai đó'],
        );

        return $this->chatRepository->findById($conversationId);
    }

    public function transferGroupAdmin(int $conversationId, int $actorId, int $userId): Conversation
    {
        $this->assertGroupAdmin($conversationId, $actorId);

        if ($actorId === $userId) {
            throw new BusinessException(ErrorCode::ValidationError);
        }

        $targetParticipant = $this->chatRepository->getParticipant($conversationId, $userId);
        if (! $targetParticipant) {
            throw new BusinessException(ErrorCode::NotGroupMember);
        }

        if ($targetParticipant->isAdmin()) {
            throw new BusinessException(ErrorCode::ValidationError);
        }

        $this->chatRepository->updateParticipantRole($conversationId, $actorId, ConversationRole::Member);
        $this->chatRepository->updateParticipantRole($conversationId, $userId, ConversationRole::Admin);

        $target = User::find($userId);
        $this->createSystemMessage(
            $conversationId,
            $actorId,
            SystemMessageAction::AdminPromoted,
            ['target_id' => $userId, 'target_name' => $target?->full_name ?? 'Ai đó'],
        );

        return $this->chatRepository->findById($conversationId);
    }

    public function leaveGroup(int $conversationId, int $userId): void
    {
        $conversation = $this->assertGroupConversation($conversationId);
        $participant = $this->chatRepository->getParticipant($conversationId, $userId);

        if (! $participant) {
            throw new BusinessException(ErrorCode::NotGroupMember);
        }

        if ($participant->isAdmin()) {
            $adminCount = $this->chatRepository->getAdminCount($conversationId);
            $memberCount = $this->chatRepository->countParticipants($conversationId);

            if ($adminCount <= 1 && $memberCount > 1) {
                throw new BusinessException(ErrorCode::LastAdminCannotLeave);
            }
        }

        $this->chatRepository->removeParticipant($conversationId, $userId);
        $this->createSystemMessage($conversationId, $userId, SystemMessageAction::MemberLeft);

        if ($this->chatRepository->countParticipants($conversationId) === 0) {
            $conversation->delete();
        }
    }

    public function getGroupMembers(int $conversationId, int $userId): Collection
    {
        $this->assertParticipant($conversationId, $userId);
        $this->assertGroupConversation($conversationId);

        return $this->chatRepository->getParticipants($conversationId);
    }

    private function assertParticipant(int $conversationId, int $userId): void
    {
        $conversation = $this->chatRepository->conversationBelongsToUser($conversationId, $userId);

        if (! $conversation) {
            $exists = $this->chatRepository->findById($conversationId);
            if (! $exists) {
                throw new BusinessException(ErrorCode::ConversationNotFound);
            }

            throw new BusinessException(ErrorCode::UnauthorizedConversationAccess);
        }
    }

    private function createSystemMessage(int $conversationId, int $actorId, SystemMessageAction $action, array $extra = []): Message
    {
        $message = $this->chatRepository->createMessage([
            'conversation_id' => $conversationId,
            'sender_id' => $actorId,
            'type' => MessageType::System->value,
            'body' => $this->buildSystemMessageBody($action, $actorId, $extra),
            'metadata' => array_merge(['action' => $action->value, 'actor_id' => $actorId], $extra),
        ]);

        try {
            MessageSent::dispatch($message);
        } catch (\Throwable $e) {
            logger()->error('Failed to broadcast system MessageSent: '.$e->getMessage(), [
                'exception' => $e,
                'message_id' => $message->id,
            ]);
        }

        return $message;
    }

    private function buildSystemMessageBody(SystemMessageAction $action, int $actorId, array $extra): string
    {
        $actorName = User::find($actorId)?->full_name ?? 'Ai đó';

        return match ($action) {
            SystemMessageAction::GroupCreated => "{$actorName} đã tạo nhóm",
            SystemMessageAction::MemberAdded => "{$actorName} đã thêm {$extra['target_name']} vào nhóm",
            SystemMessageAction::MemberRemoved => "{$actorName} đã xóa {$extra['target_name']} khỏi nhóm",
            SystemMessageAction::MemberLeft => "{$actorName} đã rời nhóm",
            SystemMessageAction::GroupRenamed => "{$actorName} đã đổi tên nhóm thành \"{$extra['new_name']}\"",
            SystemMessageAction::AvatarChanged => "{$actorName} đã đổi ảnh nhóm",
            SystemMessageAction::AdminPromoted => "{$actorName} đã cấp quyền admin cho {$extra['target_name']}",
        };
    }

    private function assertGroupConversation(int $conversationId): Conversation
    {
        $conversation = $this->chatRepository->findById($conversationId);

        if (! $conversation) {
            throw new BusinessException(ErrorCode::GroupNotFound);
        }

        if (! $conversation->isGroup()) {
            throw new BusinessException(ErrorCode::CannotModifyPrivateChat);
        }

        return $conversation;
    }

    private function assertGroupAdmin(int $conversationId, int $userId): void
    {
        $this->assertGroupConversation($conversationId);
        $participant = $this->chatRepository->getParticipant($conversationId, $userId);

        if (! $participant || ! $participant->isAdmin()) {
            throw new BusinessException(ErrorCode::NotGroupAdmin);
        }
    }
}
