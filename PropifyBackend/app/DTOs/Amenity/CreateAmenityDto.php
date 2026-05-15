<?php

namespace App\DTOs\Amenity;

final readonly class CreateAmenityDto
{
    public function __construct(
        public string $name,
        public ?string $icon,
        public int $orderIndex,
    ) {}
}
