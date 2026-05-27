<?php

namespace App\Listeners\Listing;

use App\Events\Listing\ListingPackageUpgraded;
use App\Events\Listing\ListingSaved;
use Illuminate\Support\Facades\Cache;

final class ClearPublicListingCache
{
    public function handle(ListingPackageUpgraded|ListingSaved $event): void
    {
        Cache::tags(['listings:public'])->flush();
    }
}
