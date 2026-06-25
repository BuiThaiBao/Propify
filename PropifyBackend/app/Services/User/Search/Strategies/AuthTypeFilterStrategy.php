<?php

namespace App\Services\User\Search\Strategies;

use App\Services\User\Search\UserSearchStrategy;
use Illuminate\Database\Eloquent\Builder;

final class AuthTypeFilterStrategy implements UserSearchStrategy
{
    /**
     * {@inheritdoc}
     */
    public function apply(Builder $query, mixed $value): Builder
    {
        if (empty($value) || $value === 'all') {
            return $query;
        }

        if ($value === 'google') {
            return $query->whereNotNull('google_id');
        }

        if ($value === 'email') {
            return $query->whereNull('google_id');
        }

        return $query;
    }
}
