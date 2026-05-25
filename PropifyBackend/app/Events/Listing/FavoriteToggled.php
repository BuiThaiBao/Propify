<?php

namespace App\Events\Listing;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FavoriteToggled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $listingId,
        public readonly bool $isFavorited
    ) {}
}
