<?php

namespace App\Services\User\Observers\Impl;

use App\Models\User;
use App\Services\Notification\InAppNotificationService;
use App\Services\User\Observers\UserObserverInterface;

final class NotificationObserver implements UserObserverInterface
{
    /**
     * NotificationObserver constructor.
     */
    public function __construct(private readonly InAppNotificationService $notificationService) {}

    /**
     * {@inheritdoc}
     */
    public function update(User $user, string $event, array $metadata = []): void
    {
        if ($event === 'locked') {
            $reasonLabel = $metadata['reason_label'] ?? 'Vi phạm quy định hệ thống';
            $reasonText = $metadata['reason_text'] ?? '';
            $message = "Tài khoản của bạn đã bị khóa. Lý do: {$reasonLabel}.";
            if (! empty($reasonText)) {
                $message .= " Chi tiết: {$reasonText}";
            }

            $this->notificationService->create(
                userId: $user->id,
                type: 'user_locked',
                title: 'Tài khoản của bạn đã bị khóa',
                message: $message,
                data: [
                    'reason_code' => $metadata['reason_code'] ?? null,
                    'reason_label' => $reasonLabel,
                    'reason_text' => $reasonText,
                ]
            );
        } elseif ($event === 'unlocked') {
            $this->notificationService->create(
                userId: $user->id,
                type: 'user_unlocked',
                title: 'Tài khoản của bạn đã được mở khóa',
                message: 'Tài khoản của bạn đã được kích hoạt trở lại. Bạn có thể sử dụng các chức năng của hệ thống.',
                data: []
            );
        }
    }
}
