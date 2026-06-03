<?php

namespace App\Services\Notification\Channel;

use App\Enums\NotificationChanelType;
use App\Enums\NotificationType;
use App\Events\Notification\NotificationSent;
use App\Models\Notification;
use App\Models\User;

final class DatabaseChannel implements NotificationChannel
{
    public function name(): NotificationChanelType
    {
        return NotificationChanelType::DATABASE;
    }

    public function send(User $user, NotificationType $template, array $data = []): void
    {
        $notification = Notification::query()->create([
            'user_id' => $user->id,
            'type' => $template->value,
            'title' => $this->titleFor($template, $data),
            'content' => $this->contentFor($template, $data),
            'data' => $data,
        ]);

        NotificationSent::dispatch($notification);
    }

    private function titleFor(NotificationType $template, array $data): string
    {
        return match ($template) {
            NotificationType::PACKAGE_UPGRADED => 'Nâng cấp gói tin thành công',
            NotificationType::PACKAGE_EXPIRING => 'Gói tin sắp hết hạn',
            NotificationType::WELCOME => 'Chào mừng bạn đến với Propify',
            NotificationType::VERIFY_EMAIL => 'Mã xác thực email',
            NotificationType::FORGOT_PASSWORD => 'Mã đặt lại mật khẩu',
            NotificationType::PASSWORD_RESET => 'Mật khẩu đã được đặt lại',
        };
    }

    private function contentFor(NotificationType $template, array $data): string
    {
        return match ($template) {
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
            NotificationType::WELCOME => 'Tài khoản của bạn đã được tạo thành công.',
            NotificationType::VERIFY_EMAIL => 'Mã OTP xác thực tài khoản đã được gửi.',
            NotificationType::FORGOT_PASSWORD => 'Mã OTP đặt lại mật khẩu đã được gửi.',
            NotificationType::PASSWORD_RESET => 'Mật khẩu của bạn đã được đặt lại thành công.',
        };
    }
}
