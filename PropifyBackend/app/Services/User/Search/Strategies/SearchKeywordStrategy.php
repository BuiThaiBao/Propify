<?php

namespace App\Services\User\Search\Strategies;

use App\Services\User\Search\UserSearchStrategy;
use Illuminate\Database\Eloquent\Builder;

final class SearchKeywordStrategy implements UserSearchStrategy
{
    /**
     * {@inheritdoc}
     */
    public function apply(Builder $query, mixed $value): Builder
    {
        if (empty($value)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($value) {
            $q->where('full_name', 'like', "%{$value}%")
                ->orWhere('email', 'like', "%{$value}%")
                ->orWhere('phone', 'like', "%{$value}%");
        });
    }
}
