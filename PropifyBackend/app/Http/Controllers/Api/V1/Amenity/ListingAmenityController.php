<?php

namespace App\Http\Controllers\Api\V1\Amenity;

use App\Helpers\ApiResponse;
use App\Http\Requests\Amenity\UpdateListingAmenitiesRequest;
use App\Http\Resources\AmenityResource;
use App\Services\Amenity\ListingAmenityService;
use Illuminate\Http\JsonResponse;

final class ListingAmenityController
{
    public function __construct(
        private readonly ListingAmenityService $listingAmenityService,
    ) {}

    public function index(int $listingId): JsonResponse
    {
        return ApiResponse::success(
            data: AmenityResource::collection($this->listingAmenityService->getByListing(request()->user(), $listingId)),
            message: 'Lấy danh sách tiện ích của tin đăng thành công.',
        );
    }

    public function update(UpdateListingAmenitiesRequest $request, int $listingId): JsonResponse
    {
        return ApiResponse::success(
            data: AmenityResource::collection($this->listingAmenityService->updateForListing($request->user(), $listingId, $request->toDto())),
            message: 'Cập nhật tiện ích của tin đăng thành công.',
        );
    }
}
