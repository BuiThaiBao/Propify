<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\CreateListingRequest;
use App\Http\Requests\GetMyListingsRequest;
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

    public function myListings(GetMyListingsRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->listingService->getMyListings(
            user: $request->user(),
            status: $request->input('status'),
            demandType: $request->input('demand_type'),
            keyword: $request->input('keyword'),
            perPage: $perPage,
        );

        return ApiResponse::success(
            data: ListingResource::collection($paginator->items()),
            message: 'Lay danh sach tin dang cua ban thanh cong.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }
}
