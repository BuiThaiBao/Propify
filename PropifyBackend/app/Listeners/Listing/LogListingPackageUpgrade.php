<?php

namespace App\Listeners\Listing;

use App\Events\Listing\ListingPackageUpgraded;
use App\Models\Package;
use Illuminate\Support\Facades\Log;

final class LogListingPackageUpgrade
{
    public function handle(ListingPackageUpgraded $event): void
    {
        $package = Package::find($event->newPackageId);

        Log::info('[ListingService] Listing upgraded', [
            'listing_id' => $event->listingId,
            'user_id' => $event->userId,
            'package' => $package?->slug ?? $event->newPackageId,
            'duration' => $event->durationDays.' days',
            'amount' => $event->amount,
            'expires_at' => $event->expiresAt->toIso8601String(),
            'is_renewal' => $event->isRenewal,
        ]);
    }
}
