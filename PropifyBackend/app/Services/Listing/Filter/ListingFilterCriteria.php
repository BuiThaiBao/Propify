<?php

namespace App\Services\Listing\Filter;

/**
 * Gom các tiêu chí lọc/tìm kiếm tin đăng (phía admin) thành một value object,
 * thay cho việc truyền 8–9 tham số rời rạc qua nhiều tầng (Builder pattern).
 */
final class ListingFilterCriteria
{
    public function __construct(
        public readonly ?string $status = null,
        public readonly ?string $demandType = null,
        public readonly ?string $keyword = null,
        public readonly ?string $searchField = 'title',
        public readonly ?string $priceRange = null,
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null,
        public readonly ?int $packageId = null,
    ) {}

    public static function forAdmin(
        ?string $status,
        ?string $demandType,
        ?string $keyword,
        ?string $searchField = 'title',
        ?string $priceRange = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?int $packageId = null,
    ): self {
        return new self(
            status: $status,
            demandType: $demandType,
            keyword: $keyword,
            searchField: $searchField,
            priceRange: $priceRange,
            minPrice: $minPrice,
            maxPrice: $maxPrice,
            packageId: $packageId,
        );
    }
}
