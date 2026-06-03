<?php

namespace App\Listeners\Listing;

use App\Enums\NotificationChannelType;
use App\Enums\NotificationType;
use App\Events\Listing\ListingPackageUpgraded;
use App\Models\Listing;
use App\Models\Package;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendPackageUpgradeNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(ListingPackageUpgraded $event): void
    {
        $listing = Listing::query()->findOrFail($event->listingId);
        $user = User::query()->findOrFail($event->userId);
        $newPackage = Package::query()->findOrFail($event->newPackageId);

        $this->notificationService->send(
            user: $user,
            type: NotificationType::PACKAGE_UPGRADED,
            data: [
                'listing_id' => $listing->id,
                'listing_title' => $listing->title,
                'package_id' => $newPackage->id,
                'package_name' => $newPackage->name,
                'duration_days' => $event->durationDays,
                'amount' => $event->amount,
                'expires_at' => $event->expiresAt->toDateTimeString(),
                'is_renewal' => $event->isRenewal,
            ],
            channels: [NotificationChannelType::EMAIL, NotificationChannelType::DATABASE],
        );
    }
}
