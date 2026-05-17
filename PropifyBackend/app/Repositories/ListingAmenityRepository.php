<?php

namespace App\Repositories;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Collection;

interface ListingAmenityRepository
{
    public function findListingWithProperty(int $listingId): ?Listing;

    public function getListingAmenities(Listing $listing): Collection;

    public function syncListingAmenities(Listing $listing, array $amenities): Collection;
}
