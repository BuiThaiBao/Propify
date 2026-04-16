<?php

namespace App\Services\Chat;

use App\DTOs\Chat\GetOrCreateConversationDto;
use App\DTOs\Chat\SendMessageDto;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;

interface ChatService
{
    /**
     * Lấy conversation giữa 2 user. Tự động tạo mới nếu chưa tồn tại.
     * Unique constraint đảm bảo không bao giờ có duplicate conversation.
     */
    public function getOrCreateConversation(GetOrCreateConversationDto $dto): Conversation;

    /**
     * Lấy danh sách tất cả conversations của user (có last message & unread count).
     *
     * @return Collection<Conversation>
     */
    public function getConversations(int $userId): Collection;

    /**
     * Lấy messages có cursor pagination (infinite scroll từ dưới lên).
     */
    public function getMessages(int $conversationId, int $userId, ?string $cursor): CursorPaginator;

    /**
     * Gửi message. Broadcast qua Redis queue → Reverb.
     * Throw UnauthorizedConversationAccessException nếu user không phải participant.
     */
    public function sendMessage(SendMessageDto $dto): Message;

    /**
     * Đánh dấu tất cả messages trong conversation là đã đọc cho user.
     */
    public function markAsRead(int $conversationId, int $userId): void;
}
