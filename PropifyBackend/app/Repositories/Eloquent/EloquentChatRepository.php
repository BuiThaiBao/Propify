<?php

namespace App\Repositories\Eloquent;

use App\Enums\ConversationRole;
use App\Enums\ConversationType;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Repositories\ChatRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class EloquentChatRepository implements ChatRepository
{
    public function __construct(
        protected readonly Conversation $conversationModel,
        protected readonly Message $messageModel,
        protected readonly ConversationParticipant $participantModel,
    ) {}

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

        $conversation = DB::transaction(function () use ($minId, $maxId, $listingId) {
            $conversation = $this->conversationModel->create([
                'participant_a_id' => $minId,
                'participant_b_id' => $maxId,
                'listing_id' => $listingId,
            ]);

            // Seed participants
            $this->participantModel->create([
                'conversation_id' => $conversation->id,
                'user_id' => $minId,
            ]);
            $this->participantModel->create([
                'conversation_id' => $conversation->id,
                'user_id' => $maxId,
            ]);

            return $conversation;
        });

        // Invalidate cache cho cả 2 participants
        Cache::forget("chat:conversations:{$minId}");
        Cache::forget("chat:conversations:{$maxId}");

        return $conversation;
    }

    public function getConversationsForUser(int $userId): Collection
    {
        return Cache::remember("chat:conversations:{$userId}", 30, function () use ($userId) {
            return $this->conversationModel
                ->whereHas('conversationParticipants', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->with([
                    'creator:id,full_name,avatar_url',
                    'participantA:id,full_name,avatar_url',
                    'participantB:id,full_name,avatar_url',
                    'latestMessage.sender:id,full_name',
                    'conversationParticipants.user:id,full_name,avatar_url',
                ])
                ->latest('updated_at')
                ->get();
        });
    }

    public function getMessages(int $conversationId, ?string $cursor, int $perPage = 20): CursorPaginator
    {
        return $this->messageModel
            ->select(['id', 'conversation_id', 'sender_id', 'type', 'body', 'file_url', 'created_at'])
            ->where('conversation_id', $conversationId)
            ->where('is_deleted', false)
            ->with('sender:id,full_name,avatar_url')
            ->orderByDesc('id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    /**
     * Kiểm tra conversation tồn tại và user là participant — 1 query duy nhất.
     */
    public function conversationBelongsToUser(int $conversationId, int $userId): ?Conversation
    {
        return $this->conversationModel
            ->where('id', $conversationId)
            ->whereHas('conversationParticipants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->first();
    }

    public function createMessage(array $data): Message
    {
        $message = $this->messageModel->create($data);

        $conv = $this->conversationModel
            ->where('id', $data['conversation_id'])
            ->first(['id', 'participant_a_id', 'participant_b_id']);

        if ($conv) {
            $conv->update(['updated_at' => now()]);
            $participantIds = $this->participantModel
                ->where('conversation_id', $conv->id)
                ->pluck('user_id');

            foreach ($participantIds as $participantId) {
                Cache::forget("chat:conversations:{$participantId}");
            }
        }

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
                'creator:id,full_name,avatar_url',
                'participantA:id,full_name,avatar_url',
                'participantB:id,full_name,avatar_url',
                'latestMessage.sender:id,full_name',
                'conversationParticipants.user:id,full_name,avatar_url',
            ])
            ->find($conversationId);
    }

    public function countUnread(int $conversationId, int $userId): int
    {
        $participant = $this->participantModel
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        if (! $participant) {
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

    public function createGroupConversation(int $creatorId, string $name, ?string $avatarUrl): Conversation
    {
        return DB::transaction(function () use ($creatorId, $name, $avatarUrl) {
            $conversation = $this->conversationModel->create([
                'type' => ConversationType::Group->value,
                'name' => $name,
                'avatar_url' => $avatarUrl,
                'creator_id' => $creatorId,
                'participant_a_id' => null,
                'participant_b_id' => null,
                'listing_id' => null,
            ]);

            $this->participantModel->create([
                'conversation_id' => $conversation->id,
                'user_id' => $creatorId,
                'role' => ConversationRole::Admin->value,
                'joined_at' => now(),
            ]);

            Cache::forget("chat:conversations:{$creatorId}");

            return $conversation;
        });
    }

    public function addParticipants(int $conversationId, array $userIds, ConversationRole $role = ConversationRole::Member): void
    {
        $now = now();
        $records = array_map(static fn (int $userId) => [
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'role' => $role->value,
            'joined_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ], $userIds);

        $this->participantModel->insert($records);

        foreach ($userIds as $userId) {
            Cache::forget("chat:conversations:{$userId}");
        }
    }

    public function removeParticipant(int $conversationId, int $userId): void
    {
        $this->participantModel
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->delete();

        Cache::forget("chat:conversations:{$userId}");
    }

    public function updateConversation(int $conversationId, array $data): Conversation
    {
        $conversation = $this->conversationModel->findOrFail($conversationId);
        $conversation->update($data);

        $participantIds = $this->participantModel
            ->where('conversation_id', $conversationId)
            ->pluck('user_id');

        foreach ($participantIds as $userId) {
            Cache::forget("chat:conversations:{$userId}");
        }

        return $this->findById($conversationId);
    }

    public function getParticipants(int $conversationId): Collection
    {
        return $this->participantModel
            ->where('conversation_id', $conversationId)
            ->with('user:id,full_name,avatar_url')
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('joined_at')
            ->get();
    }

    public function getParticipant(int $conversationId, int $userId): ?ConversationParticipant
    {
        return $this->participantModel
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();
    }

    public function countParticipants(int $conversationId): int
    {
        return $this->participantModel
            ->where('conversation_id', $conversationId)
            ->count();
    }

    public function updateParticipantRole(int $conversationId, int $userId, ConversationRole $role): void
    {
        $this->participantModel
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update(['role' => $role->value]);
    }

    public function getAdminCount(int $conversationId): int
    {
        return $this->participantModel
            ->where('conversation_id', $conversationId)
            ->where('role', ConversationRole::Admin->value)
            ->count();
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
