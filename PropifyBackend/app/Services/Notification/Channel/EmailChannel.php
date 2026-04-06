<?php

namespace App\Services\Notification\Channel;

use App\Enums\MailType;
use App\Enums\NotificationChanelType;
use App\Mail\Auth\ForgotPasswordMail;
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
            MailType::WELCOME        => new WelcomeMail($user, $data),
            MailType::VERIFY_EMAIL   => new VerifyEmailMail($user, $data),
            MailType::FORGOT_PASSWORD => new ForgotPasswordMail($user, $data),
            default => throw new \InvalidArgumentException("Unsupported mail template: {$mailType->value}"),
        };

        // Theo yêu cầu: OTP gửi đồng bộ (chờ gửi xong mới đi tiếp),
        // Còn Welcome Mail thì ném vào Queue (gửi ngầm) để verify-otp không bị chậm 6s.
        if ($mailType === MailType::WELCOME) {
            Mail::to($user->email)->queue($mailable);
        } else {
            Mail::to($user->email)->send($mailable);
        }
    }
}