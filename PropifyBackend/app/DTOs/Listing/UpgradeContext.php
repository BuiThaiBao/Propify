<?php

namespace App\DTOs\Listing;

use App\Models\Listing;
use App\Models\Package;
use App\Models\PackagePricing;
use App\Models\User;
use Carbon\CarbonInterface;

final readonly class UpgradeContext
{
    public function __construct(
        public User $user,
        public Listing $listing,
        public Package $newPackage,
        public PackagePricing $pricing,
        public int $durationDays,
        public ?Package $currentPackage,
        public CarbonInterface $now,
    ) {
    }

    public function isRenewal(): bool
    {
        return $this->listing->package_id !== null
            && $this->listing->package_id === $this->newPackage->id;
    }
}
