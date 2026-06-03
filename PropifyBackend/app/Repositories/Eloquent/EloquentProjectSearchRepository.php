<?php

namespace App\Repositories\Eloquent;

use App\Models\Property;
use App\Repositories\ProjectSearchRepository;
use App\Support\PropertySearchText;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class EloquentProjectSearchRepository implements ProjectSearchRepository
{
    public function search(string $keyword, int $page, int $size): LengthAwarePaginator
    {
        $normalizedKeyword = PropertySearchText::normalize($keyword);
        $tokens = $this->tokenize($normalizedKeyword);

        $query = Property::query()
            ->select([
                'properties.id',
                'properties.province',
                'properties.ward',
                'properties.street_code',
                'properties.project_name',
                'properties.address_detail',
            ])
            ->withCount([
                'listings as active_listings_count' => fn ($listingQuery) => $listingQuery->where('status', 'ACTIVE'),
            ])
            ->whereHas('listings', fn ($listingQuery) => $listingQuery->where('status', 'ACTIVE'));

        if ($this->shouldUseFullTextSearch($tokens)) {
            $booleanQuery = $this->toBooleanSearchQuery($tokens);

            $query
                ->selectRaw(
                    'MATCH(properties.search_text) AGAINST (? IN BOOLEAN MODE) as search_relevance',
                    [$booleanQuery],
                )
                ->whereRaw(
                    'MATCH(properties.search_text) AGAINST (? IN BOOLEAN MODE)',
                    [$booleanQuery],
                )
                ->orderByDesc('search_relevance');
        } else {
            $like = '%'.$normalizedKeyword.'%';

            $query
                ->selectRaw('CASE WHEN properties.search_text LIKE ? THEN 1 ELSE 0 END as search_relevance', [$like])
                ->where('properties.search_text', 'like', $like)
                ->orderByDesc('search_relevance');
        }

        return $query
            ->orderByDesc('active_listings_count')
            ->orderByDesc('properties.id')
            ->paginate(
                perPage: $size,
                columns: ['*'],
                pageName: 'page',
                page: $page,
            );
    }

    private function shouldUseFullTextSearch(array $tokens): bool
    {
        if ($tokens === []) {
            return false;
        }

        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        foreach ($tokens as $token) {
            if (mb_strlen($token, 'UTF-8') < 3) {
                return false;
            }
        }

        return true;
    }

    private function toBooleanSearchQuery(array $tokens): string
    {
        return implode(' ', array_map(
            static fn (string $token) => sprintf('+%s*', $token),
            $tokens,
        ));
    }

    private function tokenize(string $keyword): array
    {
        return array_values(array_filter(
            preg_split('/\s+/u', $keyword) ?: [],
            static fn (string $token) => $token !== '',
        ));
    }
}
