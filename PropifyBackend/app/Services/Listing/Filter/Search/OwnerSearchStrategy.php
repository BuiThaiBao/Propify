<?php

namespace App\Services\Listing\Filter\Search;

use App\Support\ListingSearchExpression;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tìm theo người đăng: họ tên / email / số điện thoại.
 */
final class OwnerSearchStrategy implements SearchFieldStrategy
{
    public function apply(Builder $query, string $normalizedKeyword): void
    {
        $like = '%'.$normalizedKeyword.'%';

        $query->whereHas('owner', function ($ownerQuery) use ($like) {
            $ownerQuery
                ->whereRaw(ListingSearchExpression::column('full_name').' LIKE ?', [$like])
                ->orWhereRaw(ListingSearchExpression::column('email').' LIKE ?', [$like])
                ->orWhere('phone', 'like', $like);
        });
    }
}
