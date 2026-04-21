<?php

namespace App\Services;

use App\DTOs\CreateListingDto;
use App\Models\Listing;
use App\Models\User;

interface ListingService
{
    public function create(User $user, CreateListingDto $dto): Listing;
}
