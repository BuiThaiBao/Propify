<?php

namespace App\Services\Listing\Sorting\Strategies;

use App\Services\Listing\Sorting\ListingSortingStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class DefaultPackageScoreSortingStrategy implements ListingSortingStrategy
{
    public function apply(Builder $query): Builder
    {
        $driver = DB::connection()->getDriverName();
        $hoursSincePublished = $driver === 'sqlite'
            ? "((julianday('now') - julianday(COALESCE(listings.published_at, listings.created_at))) * 24.0)"
            : "TIMESTAMPDIFF(HOUR, COALESCE(listings.published_at, listings.created_at), NOW())";

        $finalScoreFormula = $driver === 'sqlite'
            ? "(COALESCE(listings.score, 0) * COALESCE(packages.multiplier, 1.0) * (1.0 / (1.0 + {$hoursSincePublished} / 24.0)))"
            : "(COALESCE(listings.score, 0) * COALESCE(packages.multiplier, 1.0) * (1.0 / (1.0 + {$hoursSincePublished} / 24.0)) * EXP(-COALESCE(packages.decay_rate, 0.05) * {$hoursSincePublished}))";

        return $query
            ->selectRaw("
                COALESCE(packages.priority, 1) AS pkg_priority,
                {$finalScoreFormula} AS final_score
            ")
            ->leftJoin('packages', 'listings.package_id', '=', 'packages.id')
            ->orderByDesc('pkg_priority')
            ->orderByDesc('final_score');
    }
}
