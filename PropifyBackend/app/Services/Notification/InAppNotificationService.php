<?php

namespace App\Services\Notification;

use App\Events\Notification\NotificationSent;
use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class InAppNotificationService
{
    public function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): Notification {
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'content' => $message,
            'data' => $data,
        ]);

        NotificationSent::dispatch($notification);

        return $notification;
    }

    public function paginateForUser(int $userId, int $perPage = 15, bool $unreadOnly = false): LengthAwarePaginator
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->when($unreadOnly, fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate($perPage);
    }

    public function unreadCount(int $userId): int
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function markAsRead(int $userId, string $notificationId): Notification
    {
        $notification = Notification::query()
            ->where('user_id', $userId)
            ->findOrFail($notificationId);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return $notification;
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
