<?php

namespace App\Listeners\Listing;

use App\Events\Listing\ListingPackageUpgraded;
use Illuminate\Support\Facades\Log;

final class LogListingPackageUpgrade
{
    public function handle(ListingPackageUpgraded $event): void
    {
        Log::info('[ListingService] Listing upgraded', [
            'listing_id'   => $event->listing->id,
            'user_id'      => $event->user->id,
            'package'      => $event->newPackage->slug,
            'duration'     => $event->durationDays . ' days',
            'amount'       => $event->amount,
            'expires_at'   => $event->expiresAt->toIso8601String(),
            'is_renewal'   => $event->isRenewal,
        ]);
    }
}
