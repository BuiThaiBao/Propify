<?php

namespace App\Events\Listing;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ListingPackageUpgraded implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $listingId,
        public readonly int $userId,
        public readonly ?int $oldPackageId,
        public readonly int $newPackageId,
        public readonly int $durationDays,
        public readonly string $amount,
        public readonly CarbonInterface $expiresAt,
        public readonly bool $isRenewal,
    ) {}
}
