<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Helpers\ApiResponse;
use App\Http\Resources\NotificationResource;
use App\Services\Notification\InAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class NotificationController extends Controller
{
    public function __construct(
        private readonly InAppNotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'unread' => ['nullable', 'boolean'],
        ]);

        $paginator = $this->notificationService->paginateForUser(
            userId: (int) $request->user()->id,
            perPage: (int) ($validated['per_page'] ?? 15),
            unreadOnly: (bool) ($validated['unread'] ?? false),
        );

        return ApiResponse::success(
            data: NotificationResource::collection($paginator->items()),
            message: 'Lấy danh sách thông báo thành công.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'unread_count' => $this->notificationService->unreadCount((int) $request->user()->id),
            ],
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: [
                'count' => $this->notificationService->unreadCount((int) $request->user()->id),
            ],
            message: 'Lấy số thông báo chưa đọc thành công.',
        );
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $this->notificationService->markAsRead((int) $request->user()->id, $id);

        return ApiResponse::success(
            data: new NotificationResource($notification),
            message: 'Đã đánh dấu thông báo là đã đọc.',
        );
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = $this->notificationService->markAllAsRead((int) $request->user()->id);

        return ApiResponse::success(
            data: ['updated' => $updated],
            message: 'Đã đánh dấu tất cả thông báo là đã đọc.',
        );
    }
}
