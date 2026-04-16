<?php

namespace App\Repositories;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;

interface ChatRepository
{
    /**
     * Tìm conversation theo normalized pair (participant_a < participant_b) + listing_id.
     * Trả về null nếu chưa tồn tại (tránh duplicate).
     */
    public function findConversation(int $userAId, int $userBId, ?int $listingId): ?Conversation;

    /**
     * Tạo conversation mới cùng với 2 participants.
     */
    public function createConversation(int $userAId, int $userBId, ?int $listingId): Conversation;

    /**
     * Lấy danh sách tất cả conversations của user, kèm last message + unread count.
     *
     * @return Collection<Conversation>
     */
    public function getConversationsForUser(int $userId): Collection;

    /**
     * Lấy messages của conversation theo cursor pagination (newest first).
     */
    public function getMessages(int $conversationId, ?string $cursor, int $perPage = 20): CursorPaginator;

    /**
     * Lưu message mới vào DB.
     *
     * @param array<string, mixed> $data
     */
    public function createMessage(array $data): Message;

    /**
     * Kiểm tra user có phải participant của conversation không.
     */
    public function isParticipant(int $conversationId, int $userId): bool;

    /**
     * Đánh dấu conversation đã đọc cho user.
     */
    public function markAsRead(int $conversationId, int $userId): void;

    /**
     * Tìm conversation theo ID.
     */
    public function findById(int $conversationId): ?Conversation;

    /**
     * Đếm số message chưa đọc trong conversation cho user.
     */
    public function countUnread(int $conversationId, int $userId): int;

    /**
     * Cập nhật last_seen_at cho participant.
     */
    public function updateLastSeen(int $conversationId, int $userId): void;
}
