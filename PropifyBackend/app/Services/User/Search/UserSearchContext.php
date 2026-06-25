<?php

namespace App\Services\User\Search;

use App\Services\User\Search\Strategies\AuthTypeFilterStrategy;
use App\Services\User\Search\Strategies\RoleFilterStrategy;
use App\Services\User\Search\Strategies\SearchKeywordStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class UserSearchContext
{
    /**
     * @var array<string, UserSearchStrategy>
     */
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [
            'search' => new SearchKeywordStrategy,
            'role' => new RoleFilterStrategy,
            'auth_type' => new AuthTypeFilterStrategy,
        ];
    }

    /**
     * Apply all registered strategies based on request inputs.
     */
    public function applyStrategies(Builder $query, Request $request): Builder
    {
        foreach ($this->strategies as $key => $strategy) {
            if ($request->filled($key)) {
                $query = $strategy->apply($query, $request->input($key));
            }
        }

        return $query;
    }
}
