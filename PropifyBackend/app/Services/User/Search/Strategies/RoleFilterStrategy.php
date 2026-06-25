<?php

namespace App\Services\User\Search\Strategies;

use App\Services\User\Search\UserSearchStrategy;
use Illuminate\Database\Eloquent\Builder;

final class RoleFilterStrategy implements UserSearchStrategy
{
    /**
     * {@inheritdoc}
     */
    public function apply(Builder $query, mixed $value): Builder
    {
        if (empty($value) || $value === 'all') {
            return $query;
        }

        if ($value === 'agent') {
            return $query->whereHas('listings.property', function (Builder $q) {
                $q->where('poster_type', 'BROKER');
            });
        }

        if ($value === 'user') {
            return $query->whereDoesntHave('listings.property', function (Builder $q) {
                $q->where('poster_type', 'BROKER');
            });
        }

        return $query;
    }
}
