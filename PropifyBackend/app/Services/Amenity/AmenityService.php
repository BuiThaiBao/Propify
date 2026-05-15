<?php

namespace App\Services\Amenity;

use App\DTOs\Amenity\CreateAmenityDto;
use App\DTOs\Amenity\UpdateAmenityDto;
use App\Models\Attribute;
use Illuminate\Database\Eloquent\Collection;

interface AmenityService
{
    public function getAll(): Collection;

    public function create(CreateAmenityDto $dto): Attribute;

    public function update(int $id, UpdateAmenityDto $dto): Attribute;
}
