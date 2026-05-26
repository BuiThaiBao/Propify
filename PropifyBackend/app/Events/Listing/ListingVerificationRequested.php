<?php

namespace App\Events\Listing;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ListingVerificationRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $listingId,
        public readonly int $userId
    ) {}
}
