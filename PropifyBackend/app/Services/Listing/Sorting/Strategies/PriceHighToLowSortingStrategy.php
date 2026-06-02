<?php

namespace App\Services\Listing\Sorting\Strategies;

use App\Services\Listing\Sorting\ListingSortingStrategy;
use Illuminate\Database\Eloquent\Builder;

final class PriceHighToLowSortingStrategy implements ListingSortingStrategy
{
    public function apply(Builder $query): Builder
    {
        return $query
            ->join('properties', 'listings.property_id', '=', 'properties.id')
            ->orderBy('properties.price', 'desc');
    }
}
