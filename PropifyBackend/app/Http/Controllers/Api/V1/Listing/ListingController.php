<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Helpers\ApiResponse;
use App\Http\Resources\Requests\Listing\CreateListingRequest;
use App\Http\Resources\Requests\Listing\GetMyListingsRequest;
use App\Http\Resources\ListingResource;
use App\Services\Listing\ListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            message: $listing->status === 'DRAFT'
                ? 'Luu tin nhap thanh cong.'
                : 'Tao tin dang thanh cong. Tin dang dang cho duyet.'
        );
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 12);
        $demandType = $request->input('demand_type');
        $keyword = $request->input('keyword');
        $paginator = $this->listingService->getPublicListings($demandType, $keyword, $perPage);

        return ApiResponse::success(
            data: ListingResource::collection($paginator->items()),
            message: 'Lay danh sach tin dang thanh cong.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }

    public function show(int $id): JsonResponse
    {
        $listing = $this->listingService->getListingDetails($id);

        return ApiResponse::success(
            data: new ListingResource($listing),
            message: 'Lay chi tiet tin dang thanh cong.'
        );
    }

    public function showMine(Request $request, int $id): JsonResponse
    {
        $listing = $this->listingService->getOwnedListingDetails($request->user(), $id);

        return ApiResponse::success(
            data: new ListingResource($listing),
            message: 'Lay chi tiet tin dang cua ban thanh cong.'
        );
    }

    public function update(CreateListingRequest $request, int $id): JsonResponse
    {
        $listing = $this->listingService->update($request->user(), $id, $request->toDto());

        return ApiResponse::success(
            data: new ListingResource($listing),
            message: 'Cap nhat tin dang thanh cong. Tin dang dang cho duyet lai.'
        );
    }

    public function updateVerification(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'identity_card_front' => ['required', 'string', 'url', 'max:2048'],
            'identity_card_back' => ['required', 'string', 'url', 'max:2048'],
            'legal_documents' => ['nullable', 'array', 'max:5'],
            'legal_documents.*' => ['string', 'url', 'max:2048'],
            'public_info_agreed' => ['nullable', 'boolean'],
        ]);

        $listing = $this->listingService->updateVerification($request->user(), $id, $validated);

        return ApiResponse::success(
            data: new ListingResource($listing),
            message: 'Cap nhat thong tin xac thuc bat dong san thanh cong.'
        );
    }

    public function lock(Request $request, int $id): JsonResponse
    {
        $listing = $this->listingService->lock($request->user(), $id);

        return ApiResponse::success(
            data: new ListingResource($listing),
            message: 'Khoa tin dang thanh cong.'
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

        $countsQuery = \App\Models\Listing::where('owner_id', $request->user()->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        $countsQuery['ALL'] = array_sum($countsQuery);

        return ApiResponse::success(
            data: ListingResource::collection($paginator->items()),
            message: 'Lay danh sach tin dang cua ban thanh cong.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'counts' => $countsQuery,
            ],
        );
    }

}
