<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Helpers\ApiResponse;
use App\Http\Resources\ListingResource;
use App\Http\Resources\Requests\Listing\UpgradeListingRequest;
use App\Services\Listing\ListingService;
use Illuminate\Http\JsonResponse;

final class ListingUpgradeController
{
    public function __construct(
        private readonly ListingService $listingService,
    ) {
    }

    /**
     * Nâng cấp gói tin cho listing.
     * POST /api/v1/listings/{id}/upgrade
     */
    public function upgrade(UpgradeListingRequest $request, int $id): JsonResponse
    {
        $listing = $this->listingService->upgradeListing(
            user: $request->user(),
            listingId: $id,
            packageId: (int) $request->validated('package_id'),
            durationDays: (int) $request->validated('duration_days'),
        );

        return ApiResponse::success(
            data: new ListingResource($listing),
            message: 'Nâng cấp gói tin thành công!'
        );
    }
}
