<?php

namespace App\Services\Listing\Filter\Search;

use App\Support\ListingSearchExpression;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tìm theo địa chỉ/bất động sản: địa chỉ chi tiết, dự án, mã đường/phường/tỉnh...
 */
final class AddressSearchStrategy implements SearchFieldStrategy
{
    public function apply(Builder $query, string $normalizedKeyword): void
    {
        $like = '%'.$normalizedKeyword.'%';

        $query->whereHas('property', function ($propertyQuery) use ($like) {
            $propertyQuery
                ->whereRaw(ListingSearchExpression::column('address_detail').' LIKE ?', [$like])
                ->orWhereRaw(ListingSearchExpression::column('project_name').' LIKE ?', [$like])
                ->orWhereRaw(ListingSearchExpression::column('street_code').' LIKE ?', [$like])
                ->orWhereRaw(ListingSearchExpression::column('province_code').' LIKE ?', [$like])
                ->orWhereRaw(ListingSearchExpression::column('ward_code').' LIKE ?', [$like])
                ->orWhereRaw(ListingSearchExpression::column('province').' LIKE ?', [$like])
                ->orWhereRaw(ListingSearchExpression::column('ward').' LIKE ?', [$like]);
        });
    }
}
