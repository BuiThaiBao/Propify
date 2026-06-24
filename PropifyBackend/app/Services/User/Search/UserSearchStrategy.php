<?php

namespace App\Services\User\Search;

use Illuminate\Database\Eloquent\Builder;

interface UserSearchStrategy
{
    /**
     * Apply the search/filter criteria to the query builder.
     */
    public function apply(Builder $query, mixed $value): Builder;
}
