<?php

namespace App\Listeners\Auth;

use App\Enums\MailType;
use App\Enums\NotificationChanelType;
use App\Events\Auth\UserRegistered;
use App\Services\Notification\NotificationService;

/**
 * Gửi email chào mừng sau khi user đăng ký thành công.
 * Sử dụng thẻ afterResponse() để gửi email SAU khi API trả về kết quả cho Frontend,
 * giúp Frontend không bị lag (không cần queue worker).
 */
final class SendWelcomeNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(UserRegistered $event): void
    {
        $this->notificationService->send(
            user: $event->user,
            template: MailType::WELCOME,
            channels: [NotificationChanelType::EMAIL],
        );
    }
}
