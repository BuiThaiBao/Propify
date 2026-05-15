<?php

namespace App\Repositories\Eloquent;

use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Repositories\AmenityRepository;
use Illuminate\Database\Eloquent\Collection;

final class EloquentAmenityRepository implements AmenityRepository
{
    public function getAmenityGroup(): AttributeGroup
    {
        return AttributeGroup::query()->firstOrCreate(
            ['code' => 'amenities'],
            [
                'name' => 'Tiện ích',
                'input_type' => 'checkbox',
                'order_index' => 1,
            ],
        );
    }

    public function all(): Collection
    {
        return Attribute::query()
            ->with('group')
            ->whereHas('group', fn ($query) => $query->where('code', 'amenities'))
            ->orderBy('order_index')
            ->orderBy('name')
            ->get();
    }

    public function create(array $attributes): Attribute
    {
        return Attribute::query()->create($attributes)->load('group');
    }

    public function findAmenityById(int $id): ?Attribute
    {
        return Attribute::query()
            ->with('group')
            ->whereKey($id)
            ->whereHas('group', fn ($query) => $query->where('code', 'amenities'))
            ->first();
    }

    public function update(Attribute $amenity, array $attributes): Attribute
    {
        $amenity->update($attributes);

        return $amenity->fresh('group');
    }
}
