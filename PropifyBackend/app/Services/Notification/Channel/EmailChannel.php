<?php

namespace App\Services\Notification\Channel;

use App\Enums\MailType;
use App\Enums\NotificationChanelType;
use App\Mail\Auth\VerifyEmailMail;
use App\Mail\Auth\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

final class EmailChannel implements NotificationChannel
{
    public function name(): NotificationChanelType
    {
        return NotificationChanelType::EMAIL;
    }

    public function send(User $user, MailType $mailType, array $data = []): void
    {
        $mailable = match ($mailType) {
            MailType::WELCOME      => new WelcomeMail($user, $data),
            MailType::VERIFY_EMAIL => new VerifyEmailMail($user, $data),
            // MailType::PASSWORD_RESET => new PasswordResetMail($user, $data),
            default => throw new \InvalidArgumentException("Unsupported mail template: {$mailType->value}"),
        };

        // Tạm dùng send() để debug SMTP — đổi lại queue() khi đã xác nhận hoạt động
        Mail::to($user->email)->send($mailable);
    }
}