<?php

namespace App\Events\Listing;

use App\Models\Listing;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ListingPackageExpiring
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Listing $listing,
        public readonly User $user,
        public readonly int $daysLeft,
        public readonly CarbonInterface $expiresAt,
    ) {}
}
