<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Helpers\ApiResponse;
use App\Repositories\AmenityRepository;
use App\Support\ListingPostingOptions;
use Illuminate\Http\JsonResponse;

final class ListingPostingOptionsController
{
    public function __construct(
        private readonly AmenityRepository $amenityRepository,
    ) {}

    public function __invoke(): JsonResponse
    {
        $amenities = $this->amenityRepository->getActiveAmenities()
            ->map(fn ($attribute) => [
                'value' => $attribute->name,
                'label' => $attribute->name,
            ])
            ->values()
            ->toArray();

        return ApiResponse::success(
            data: ListingPostingOptions::all(dynamicAmenities: $amenities),
            message: 'Lay cau hinh dang tin thanh cong.'
        );
    }
}

