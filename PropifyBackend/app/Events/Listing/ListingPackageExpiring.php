<?php

namespace App\Events\Listing;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ListingPackageExpiring implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $listingId,
        public readonly int $userId,
        public readonly int $daysLeft,
        public readonly int $thresholdDays,
        public readonly CarbonInterface $expiresAt,
    ) {}
}
