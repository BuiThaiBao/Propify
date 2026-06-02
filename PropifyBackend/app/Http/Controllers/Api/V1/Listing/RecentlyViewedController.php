<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Helpers\ApiResponse;
use App\Http\Resources\ListingResource;
use App\Services\Listing\Favorite\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RecentlyViewedController
{
    public function __construct(
        private readonly FavoriteService $favoriteService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $listings = $this->favoriteService->getUserRecentlyViewed($request->user()->id);

        return ApiResponse::success(
            data: ListingResource::collection($listings),
            message: 'Lay danh sach tin da xem thanh cong.'
        );
    }
}
