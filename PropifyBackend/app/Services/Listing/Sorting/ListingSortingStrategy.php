<?php

namespace App\Services\Listing\Sorting;

use Illuminate\Database\Eloquent\Builder;

interface ListingSortingStrategy
{
    public function apply(Builder $query): Builder;
}
