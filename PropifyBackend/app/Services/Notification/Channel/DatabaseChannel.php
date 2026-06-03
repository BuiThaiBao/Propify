<?php

namespace App\Services\Notification\Channel;

use App\Enums\MailType;
use App\Enums\NotificationChannelType;
use App\Enums\NotificationType;
use App\Events\Notification\NotificationSent;
use App\Models\Notification;
use App\Models\User;

final class DatabaseChannel implements NotificationChannel
{
    public function name(): NotificationChannelType
    {
        return NotificationChannelType::DATABASE;
    }

    public function send(User $user, NotificationType|MailType $type, array $data = []): void
    {
        if ($type instanceof MailType) {
            return;
        }

        $notification = Notification::query()->create([
            'user_id' => $user->id,
            'type' => $type->value,
            'title' => $this->titleFor($type, $data),
            'content' => $this->contentFor($type, $data),
            'data' => $data,
        ]);

        NotificationSent::dispatch($notification);
    }

    private function titleFor(NotificationType $type, array $data): string
    {
        return match ($type) {
            NotificationType::PACKAGE_UPGRADED => 'Nâng cấp gói tin thành công',
            NotificationType::PACKAGE_EXPIRING => 'Gói tin sắp hết hạn',
            NotificationType::APPOINTMENT_BOOKED => 'Có người đặt lịch xem nhà',
        };
    }

    private function contentFor(NotificationType $type, array $data): string
    {
        return match ($type) {
            NotificationType::PACKAGE_UPGRADED => sprintf(
                'Tin đăng "%s" đã được nâng cấp lên gói %s.',
                $data['listing_title'] ?? 'Không xác định',
                $data['package_name'] ?? 'mới'
            ),
            NotificationType::PACKAGE_EXPIRING => sprintf(
                'Tin đăng "%s" sẽ hết hạn gói %s sau %d ngày.',
                $data['listing_title'] ?? 'Không xác định',
                $data['package_name'] ?? 'hiện tại',
                (int) ($data['days_left'] ?? 0)
            ),
            NotificationType::APPOINTMENT_BOOKED => sprintf(
                'Khách "%s" đã đặt lịch xem tin "%s" vào %s.',
                $data['viewer_name'] ?? 'Không xác định',
                $data['listing_title'] ?? 'Tin đăng',
                $data['meet_time'] ?? 'thời gian chưa xác định'
            ),
        };
    }
}
