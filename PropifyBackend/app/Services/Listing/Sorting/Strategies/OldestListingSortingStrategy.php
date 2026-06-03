<?php

namespace App\Services\Listing\Sorting\Strategies;

use App\Services\Listing\Sorting\ListingSortingStrategy;
use Illuminate\Database\Eloquent\Builder;

final class OldestListingSortingStrategy implements ListingSortingStrategy
{
    public function apply(Builder $query): Builder
    {
        return $query
            ->orderBy('listings.published_at')
            ->orderBy('listings.id');
    }
}
