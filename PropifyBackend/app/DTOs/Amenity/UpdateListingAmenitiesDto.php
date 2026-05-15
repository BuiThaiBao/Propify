<?php

namespace App\DTOs\Amenity;

final readonly class UpdateListingAmenitiesDto
{
    public function __construct(
        public array $amenities,
    ) {}
}
