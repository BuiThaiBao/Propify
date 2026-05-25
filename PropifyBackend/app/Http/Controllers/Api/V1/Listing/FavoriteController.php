<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Helpers\ApiResponse;
use App\Http\Resources\ListingResource;
use App\Services\Listing\Favorite\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FavoriteController
{
    public function __construct(
        private readonly FavoriteService $favoriteService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $listings = $this->favoriteService->getUserFavorites($request->user()->id);

        return ApiResponse::success(
            data: ListingResource::collection($listings),
            message: 'Lay danh sach tin yeu thich thanh cong.'
        );
    }

    public function ids(Request $request): JsonResponse
    {
        $ids = $this->favoriteService->getUserFavoriteIds($request->user()->id);

        return ApiResponse::success(data: $ids);
    }

    public function toggle(Request $request, int $listingId): JsonResponse
    {
        $isFavorited = $this->favoriteService->toggle($request->user()->id, $listingId);

        return ApiResponse::success(
            data: ['is_favorited' => $isFavorited],
            message: $isFavorited
                ? 'Da them tin dang vao danh sach yeu thich.'
                : 'Da bo yeu thich tin dang.'
        );
    }
}
