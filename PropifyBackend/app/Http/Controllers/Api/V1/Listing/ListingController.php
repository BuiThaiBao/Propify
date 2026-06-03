<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Helpers\ApiResponse;
use App\Http\Requests\Listing\CreateListingRequest;
use App\Http\Requests\Listing\GetMyListingsRequest;
use App\Http\Requests\Listing\StoreListingReportRequest;
use App\Http\Resources\ListingResource;
use App\Http\Resources\MapListingResource;
use App\Models\Listing;
use App\Services\Listing\ListingService;
use App\Services\Listing\Reports\ReportListingCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListingController
{
    public function __construct(
        private readonly ListingService $listingService,
        private readonly ReportListingCommand $reportListingCommand,
    ) {}

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
        $sortBy = $request->input('sort');
        $demandType = $request->input('demand_type');
        $keyword = $request->input('keyword');
        $searchField = $request->input('search_field');

        $posterType = $request->input('poster_type');
        $minPrice = $request->has('min_price') && $request->input('min_price') !== '' ? (float) $request->input('min_price') : null;
        $maxPrice = $request->has('max_price') && $request->input('max_price') !== '' ? (float) $request->input('max_price') : null;
        $minArea = $request->has('min_area') && $request->input('min_area') !== '' ? (float) $request->input('min_area') : null;
        $maxArea = $request->has('max_area') && $request->input('max_area') !== '' ? (float) $request->input('max_area') : null;

        $paginator = $this->listingService->getPublicListings(
            $sortBy,
            $demandType,
            $keyword,
            $perPage,
            $searchField,
            $posterType,
            $minPrice,
            $maxPrice,
            $minArea,
            $maxArea
        );

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

    public function mapListings(Request $request): JsonResponse
    {
        $items = $this->listingService->getMapListings(
            demandType: $request->input('demand_type'),
            keyword: $request->input('keyword'),
            searchField: $request->input('search_field'),
            posterType: $request->input('poster_type'),
            minPrice: $request->has('min_price') && $request->input('min_price') !== '' ? (float) $request->input('min_price') : null,
            maxPrice: $request->has('max_price') && $request->input('max_price') !== '' ? (float) $request->input('max_price') : null,
            minArea: $request->has('min_area') && $request->input('min_area') !== '' ? (float) $request->input('min_area') : null,
            maxArea: $request->has('max_area') && $request->input('max_area') !== '' ? (float) $request->input('max_area') : null,
        );

        return ApiResponse::success(
            data: MapListingResource::collection($items),
            message: 'Lay danh sach tin dang ban do thanh cong.'
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

    public function unlist(Request $request, int $id): JsonResponse
    {
        $listing = $this->listingService->unlist($request->user(), $id);

        return ApiResponse::success(
            data: new ListingResource($listing),
            message: 'Go tin dang thanh cong.'
        );
    }

    public function report(StoreListingReportRequest $request, int $id): JsonResponse
    {
        $reports = $this->reportListingCommand->handle($request->user(), $id, [
            ...$request->validated(),
        ]);

        return ApiResponse::created(
            data: [
                'ids' => $reports->pluck('id')->values(),
                'count' => $reports->count(),
                'status' => 'WARNING',
            ],
            message: 'Cảm ơn bạn đã phản hồi.'
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

        $countsQuery = Listing::where('owner_id', $request->user()->id)
            ->when($request->input('demand_type'), function ($query, $demandType) {
                $query->where('demand_type', $demandType);
            })
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
