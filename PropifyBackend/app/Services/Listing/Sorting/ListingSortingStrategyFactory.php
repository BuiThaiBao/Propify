<?php

namespace App\Services\Listing\Sorting;

use App\Services\Listing\Sorting\Strategies\AreaHighToLowSortingStrategy;
use App\Services\Listing\Sorting\Strategies\AreaLowToHighSortingStrategy;
use App\Services\Listing\Sorting\Strategies\DefaultPackageScoreSortingStrategy;
use App\Services\Listing\Sorting\Strategies\NewestListingSortingStrategy;
use App\Services\Listing\Sorting\Strategies\OldestListingSortingStrategy;
use App\Services\Listing\Sorting\Strategies\PriceHighToLowSortingStrategy;
use App\Services\Listing\Sorting\Strategies\PriceLowToHighSortingStrategy;

final class ListingSortingStrategyFactory
{
    public static function make(?string $sortBy): ListingSortingStrategy
    {
        return match ($sortBy) {
            'newest' => new NewestListingSortingStrategy,
            'oldest' => new OldestListingSortingStrategy,
            'price_asc' => new PriceLowToHighSortingStrategy,
            'price_desc' => new PriceHighToLowSortingStrategy,
            'area_asc' => new AreaLowToHighSortingStrategy,
            'area_desc' => new AreaHighToLowSortingStrategy,
            default => new DefaultPackageScoreSortingStrategy,
        };
    }
}
