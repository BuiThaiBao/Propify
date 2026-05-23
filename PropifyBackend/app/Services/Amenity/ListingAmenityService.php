<?php

namespace App\Services\Amenity;

use App\DTOs\Amenity\UpdateListingAmenitiesDto;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ListingAmenityService
{
    public function getByListing(User $user, int $listingId): Collection;

    public function updateForListing(User $user, int $listingId, UpdateListingAmenitiesDto $dto): Collection;
}
