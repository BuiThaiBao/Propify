<?php

namespace App\Listeners\Listing;

use App\Events\Listing\ListingPackageUpgraded;
use Illuminate\Support\Facades\Cache;

final class ClearPublicListingCache
{
    public function handle(ListingPackageUpgraded $event): void
    {
        Cache::tags(['listings:public'])->flush();
    }
}
