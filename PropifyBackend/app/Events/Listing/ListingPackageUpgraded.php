<?php

namespace App\Events\Listing;

use App\Models\Listing;
use App\Models\Package;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ListingPackageUpgraded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Listing $listing,
        public readonly User $user,
        public readonly ?Package $oldPackage,
        public readonly Package $newPackage,
        public readonly int $durationDays,
        public readonly string $amount,
        public readonly CarbonInterface $expiresAt,
        public readonly bool $isRenewal,
    ) {
    }
}
