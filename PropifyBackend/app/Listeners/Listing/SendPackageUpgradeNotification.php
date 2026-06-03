<?php

namespace App\Listeners\Listing;

use App\Enums\NotificationChanelType;
use App\Enums\NotificationType;
use App\Events\Listing\ListingPackageUpgraded;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendPackageUpgradeNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(ListingPackageUpgraded $event): void
    {
        $this->notificationService->send(
            user: $event->user,
            template: NotificationType::PACKAGE_UPGRADED,
            data: [
                'listing_id' => $event->listing->id,
                'listing_title' => $event->listing->title,
                'package_id' => $event->newPackage->id,
                'package_name' => $event->newPackage->name,
                'duration_days' => $event->durationDays,
                'amount' => $event->amount,
                'expires_at' => $event->expiresAt->toDateTimeString(),
                'is_renewal' => $event->isRenewal,
            ],
            channels: [NotificationChanelType::EMAIL, NotificationChanelType::DATABASE],
        );
    }
}
