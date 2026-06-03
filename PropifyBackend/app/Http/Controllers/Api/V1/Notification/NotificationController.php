<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Helpers\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 50));
        $user = $request->user();
        $notifications = $user->notifications()->latest()->paginate($perPage);

        return ApiResponse::success(
            data: $notifications->items(),
            meta: [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $user->notifications()->unread()->count(),
            ],
            message: 'Lấy danh sách thông báo thành công.'
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: [
                'unread_count' => $request->user()->notifications()->unread()->count(),
            ],
            message: 'Lấy số lượng thông báo chưa đọc thành công.'
        );
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = Notification::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        if (! $notification->read_at) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return ApiResponse::success(
            data: $notification->fresh(),
            message: 'Đã đánh dấu thông báo là đã đọc.'
        );
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()
            ->notifications()
            ->unread()
            ->update(['read_at' => now()]);

        return ApiResponse::success(message: 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }
}
