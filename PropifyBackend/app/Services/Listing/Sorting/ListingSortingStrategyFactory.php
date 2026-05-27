<?php

namespace App\Services\Listing\Sorting;

use App\Services\Listing\Sorting\Strategies\DefaultPackageScoreSortingStrategy;
use App\Services\Listing\Sorting\Strategies\NewestListingSortingStrategy;
use App\Services\Listing\Sorting\Strategies\PriceLowToHighSortingStrategy;

final class ListingSortingStrategyFactory
{
    public static function make(?string $sortBy): ListingSortingStrategy
    {
        return match ($sortBy) {
            'price_asc' => new PriceLowToHighSortingStrategy(),
            'newest' => new NewestListingSortingStrategy(),
            default => new DefaultPackageScoreSortingStrategy(),
        };
    }
}
