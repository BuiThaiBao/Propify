<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Helpers\ApiResponse;
use App\Support\ListingPostingOptions;
use Illuminate\Http\JsonResponse;

final class ListingPostingOptionsController
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(
            data: ListingPostingOptions::all(),
            message: 'Lay cau hinh dang tin thanh cong.'
        );
    }
}
