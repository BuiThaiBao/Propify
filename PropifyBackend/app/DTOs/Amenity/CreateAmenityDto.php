<?php

namespace App\DTOs\Amenity;

final readonly class CreateAmenityDto
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $icon = null,
        public int $orderIndex = 0,
        public bool $isActive = true,
    ) {}
}
