<?php

namespace App\Listeners\Listing;

use App\Enums\NotificationChannelType;
use App\Enums\NotificationType;
use App\Events\Listing\ListingPackageExpiring;
use App\Models\Listing;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendPackageExpiringNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(ListingPackageExpiring $event): void
    {
        $listing = Listing::query()
            ->with('package:id,name')
            ->findOrFail($event->listingId);

        $user = User::query()->findOrFail($event->userId);

        $this->notificationService->send(
            user: $user,
            type: NotificationType::PACKAGE_EXPIRING,
            data: [
                'listing_id' => $listing->id,
                'listing_title' => $listing->title,
                'package_id' => $listing->package_id,
                'package_name' => $listing->package?->name ?? 'Gói tin',
                'package_expires_at' => $event->expiresAt->toDateTimeString(),
                'days_left' => $event->daysLeft,
                'threshold_days' => $event->thresholdDays,
            ],
            channels: [NotificationChannelType::EMAIL, NotificationChannelType::DATABASE],
        );
    }
}
