<?php

namespace App\Services\Listing\Validation;

use App\DTOs\Listing\CreateListingDto;
use App\Models\User;

final readonly class ListingSubmissionValidationContext
{
    public function __construct(
        public User $user,
        public CreateListingDto $dto,
    ) {}
}
