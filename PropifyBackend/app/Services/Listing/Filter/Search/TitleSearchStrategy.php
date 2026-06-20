<?php

namespace App\Services\Listing\Filter\Search;

use App\Support\ListingSearchExpression;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tìm theo tiêu đề (mặc định). Nếu từ khoá ám chỉ thuê/bán thì mở rộng theo
 * demand_type — giữ nguyên logic gốc của buildAdminBaseQuery.
 */
final class TitleSearchStrategy implements SearchFieldStrategy
{
    public function apply(Builder $query, string $normalizedKeyword): void
    {
        $like = '%'.$normalizedKeyword.'%';
        $isRent = str_contains($normalizedKeyword, 'cho') || str_contains($normalizedKeyword, 'thue');
        $isSale = str_contains($normalizedKeyword, 'mua') || str_contains($normalizedKeyword, 'ban');

        $query->whereRaw(ListingSearchExpression::column('title').' LIKE ?', [$like]);

        if ($isRent) {
            $query->orWhere('demand_type', 'RENT');
        }
        if ($isSale) {
            $query->orWhere('demand_type', 'SALE');
        }
    }
}
