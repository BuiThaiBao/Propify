<?php

namespace App\Services\Notification\Channel;

use App\Enums\NotificationChanelType;
use App\Enums\NotificationType;
use App\Mail\Auth\ForgotPasswordMail;
use App\Mail\Auth\VerifyEmailMail;
use App\Mail\Auth\WelcomeMail;
use App\Mail\PackageExpiringMail;
use App\Mail\PackageUpgradedMail;
use App\Models\Listing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

final class EmailChannel implements NotificationChannel
{
    public function name(): NotificationChanelType
    {
        return NotificationChanelType::EMAIL;
    }

    public function send(User $user, NotificationType $mailType, array $data = []): void
    {
        $mailable = match ($mailType) {
            NotificationType::WELCOME => new WelcomeMail($user, $data),
            NotificationType::VERIFY_EMAIL => new VerifyEmailMail($user, $data),
            NotificationType::FORGOT_PASSWORD => new ForgotPasswordMail($user, $data),
            NotificationType::PACKAGE_UPGRADED => new PackageUpgradedMail($user, $data),
            NotificationType::PACKAGE_EXPIRING => new PackageExpiringMail(
                listing: Listing::query()->findOrFail($data['listing_id']),
                ownerName: $user->full_name ?? 'Quý khách',
                packageName: $data['package_name'] ?? 'Gói tin',
                daysLeft: (int) ($data['days_left'] ?? 0),
                expiresAt: Carbon::parse($data['package_expires_at'] ?? now()),
            ),
            default => throw new \InvalidArgumentException("Unsupported mail template: {$mailType->value}"),
        };

        // Theo yêu cầu: OTP gửi đồng bộ (chờ gửi xong mới đi tiếp),
        // Còn Welcome Mail thì ném vào Queue (gửi ngầm) để verify-otp không bị chậm 6s.
        if ($mailType === NotificationType::WELCOME) {
            Mail::to($user->email)->queue($mailable);
        } else {
            Mail::to($user->email)->send($mailable);
        }
    }
}
