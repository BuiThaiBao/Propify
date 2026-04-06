<?php

namespace App\Listeners\Auth;

use App\Enums\MailType;
use App\Enums\NotificationChanelType;
use App\Events\Auth\UserRegistered;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Gửi email chào mừng sau khi user đăng ký thành công.
 * Implement ShouldQueue để chạy trong background, không block request.
 */
final class SendWelcomeNotification implements ShouldQueue
{
    use InteractsWithQueue;

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
