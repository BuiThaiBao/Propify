<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\DTOs\Chat\GetOrCreateConversationDto;
use App\DTOs\Chat\SendMessageDto;
use App\Helpers\ApiResponse;
use App\Http\Requests\Chat\GetOrCreateConversationRequest;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller nhàn: nhận → gọi Service → trả Resource.
 * Không có try-catch — BusinessException được handle tự động bởi ApiExceptionHandler.
 */
final class ChatController
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {
    }

    /**
     * GET /v1/chat/conversations
     * Lấy danh sách conversations của user đang đăng nhập.
     */
    public function getConversations(Request $request): JsonResponse
    {
        $conversations = $this->chatService->getConversations(auth()->id());

        return ApiResponse::success(
            data: ConversationResource::collection($conversations),
            message: 'Danh sách cuộc trò chuyện',
        );
    }

    /**
     * POST /v1/chat/conversations
     * Lấy hoặc tạo conversation với user khác (idempotent).
     */
    public function getOrCreateConversation(GetOrCreateConversationRequest $request): JsonResponse
    {
        $dto = GetOrCreateConversationDto::fromRequest($request, auth()->id());
        $conversation = $this->chatService->getOrCreateConversation($dto);

        return ApiResponse::success(
            data: new ConversationResource($conversation),
            message: 'Cuộc trò chuyện đã sẵn sàng',
        );
    }

    /**
     * GET /v1/chat/conversations/{conversationId}/messages?cursor=xxx
     * Cursor pagination — newest first. Frontend đảo ngược để hiển thị.
     */
    public function getMessages(Request $request, int $conversationId): JsonResponse
    {
        $cursor = $request->query('cursor');

        $paginator = $this->chatService->getMessages(
            conversationId: $conversationId,
            userId: auth()->id(),
            cursor: $cursor,
        );

        // Tự động đánh dấu đã đọc khi load trang đầu tiên (không có cursor)
        if (!$cursor) {
            $this->chatService->markAsRead($conversationId, auth()->id());
        }

        return ApiResponse::success(
            data: MessageResource::collection($paginator->items()),
            message: 'Danh sách tin nhắn',
            meta: [
                'next_cursor'   => $paginator->nextCursor()?->encode(),
                'has_more'      => $paginator->hasMorePages(),
            ],
        );
    }

    /**
     * POST /v1/chat/conversations/{conversationId}/messages
     * Gửi message. HTTP 201. WebSocket broadcast async qua queue.
     */
    public function sendMessage(SendMessageRequest $request, int $conversationId): JsonResponse
    {
        $dto = SendMessageDto::fromRequest($request, $conversationId, auth()->id());
        $message = $this->chatService->sendMessage($dto);

        return ApiResponse::created(
            data: new MessageResource($message),
            message: 'Gửi tin nhắn thành công',
        );
    }

    /**
     * POST /v1/chat/conversations/{conversationId}/read
     * Đánh dấu đã đọc tất cả messages trong conversation.
     */
    public function markAsRead(int $conversationId): JsonResponse
    {
        $this->chatService->markAsRead($conversationId, auth()->id());

        return ApiResponse::success(message: 'Đã đánh dấu đã đọc');
    }
}
