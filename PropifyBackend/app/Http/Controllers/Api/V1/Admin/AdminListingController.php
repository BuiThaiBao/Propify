<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ErrorCode;
use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use App\Helpers\ApiResponse;
use App\Http\Resources\ListingResource;
use App\Services\Listing\ListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AdminListingController extends Controller
{
    public function __construct(
        private readonly ListingService $listingService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->user()->role !== UserRole::Admin) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $perPage = (int) $request->input('per_page', 20);
        $status = $request->input('status');
        $demandType = $request->input('demand_type');
        $keyword = $request->input('keyword');

        $paginator = $this->listingService->getAllForAdmin($status, $demandType, $keyword, $perPage);

        return ApiResponse::success(
            data: ListingResource::collection($paginator->items()),
            message: 'Lấy danh sách tin đăng thành công.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }
}
