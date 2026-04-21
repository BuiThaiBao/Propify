<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\CreateListingRequest;
use App\Http\Resources\ListingResource;
use App\Services\ListingService;
use Illuminate\Http\JsonResponse;

final class ListingController
{
    public function __construct(
        private readonly ListingService $listingService,
    ) {
    }

    public function store(CreateListingRequest $request): JsonResponse
    {
        $listing = $this->listingService->create($request->user(), $request->toDto());

        return ApiResponse::created(
            data: new ListingResource($listing),
            message: 'Tao tin dang thanh cong. Tin dang dang cho duyet.'
        );
    }
}
