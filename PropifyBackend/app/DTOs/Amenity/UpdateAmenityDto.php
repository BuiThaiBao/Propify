<?php

namespace App\DTOs\Amenity;

final readonly class UpdateAmenityDto
{
    public function __construct(
        public string $name,
        public ?string $icon,
        public int $orderIndex,
    ) {}
}
