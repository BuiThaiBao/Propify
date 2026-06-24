<?php

namespace App\Services\Listing\Filter\Search;

/**
 * Chọn chiến lược tìm kiếm theo search_field. Mặc định (title) cho mọi giá trị
 * không phải owner/address — giữ nguyên hành vi gốc.
 */
final class SearchFieldStrategyFactory
{
    public function for(?string $searchField): SearchFieldStrategy
    {
        return match ($searchField) {
            'owner' => new OwnerSearchStrategy,
            'address' => new AddressSearchStrategy,
            default => new TitleSearchStrategy,
        };
    }
}
