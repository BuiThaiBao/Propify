<?php

namespace App\Services\Notification\Channel;

use App\Enums\MailType;
use App\Enums\NotificationChannelType;
use App\Enums\NotificationType;
use App\Mail\AppointmentBookedMail;
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
    public function name(): NotificationChannelType
    {
        return NotificationChannelType::EMAIL;
    }

    public function send(User $user, NotificationType|MailType $type, array $data = []): void
    {
        $mailable = match ($type) {
            MailType::WELCOME => new WelcomeMail($user, $data),
            MailType::VERIFY_EMAIL => new VerifyEmailMail($user, $data),
            MailType::FORGOT_PASSWORD => new ForgotPasswordMail($user, $data),
            NotificationType::PACKAGE_UPGRADED => new PackageUpgradedMail($user, $data),
            NotificationType::PACKAGE_EXPIRING => new PackageExpiringMail(
                listing: Listing::query()->findOrFail($data['listing_id']),
                ownerName: $user->full_name ?? 'Quý khách',
                packageName: $data['package_name'] ?? 'Gói tin',
                daysLeft: (int) ($data['days_left'] ?? 0),
                expiresAt: Carbon::parse($data['package_expires_at'] ?? now()),
            ),
            NotificationType::APPOINTMENT_BOOKED => new AppointmentBookedMail($user, $data),
            default => throw new \InvalidArgumentException("Unsupported mail template: {$type->value}"),
        };

        if ($type === MailType::WELCOME) {
            Mail::to($user->email)->queue($mailable);
        } else {
            Mail::to($user->email)->send($mailable);
        }
    }
}
