<?php

namespace App\Repositories;

use App\Models\Attribute;
use App\Models\AttributeGroup;
use Illuminate\Database\Eloquent\Collection;

interface AmenityRepository
{
    public function getAmenityGroup(): AttributeGroup;

    public function all(): Collection;

    public function create(array $attributes): Attribute;

    public function findAmenityById(int $id): ?Attribute;

    public function update(Attribute $amenity, array $attributes): Attribute;
}
