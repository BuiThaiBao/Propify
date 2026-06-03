<?php

namespace App\Listeners\Listing;

use App\Enums\NotificationChanelType;
use App\Enums\NotificationType;
use App\Events\Listing\ListingPackageExpiring;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendPackageExpiringNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(ListingPackageExpiring $event): void
    {
        $this->notificationService->send(
            user: $event->user,
            template: NotificationType::PACKAGE_EXPIRING,
            data: [
                'listing_id' => $event->listing->id,
                'listing_title' => $event->listing->title,
                'package_id' => $event->listing->package_id,
                'package_name' => $event->listing->package?->name ?? 'Gói tin',
                'package_expires_at' => $event->expiresAt->toDateTimeString(),
                'days_left' => $event->daysLeft,
            ],
            channels: [NotificationChanelType::EMAIL, NotificationChanelType::DATABASE],
        );
    }
}
