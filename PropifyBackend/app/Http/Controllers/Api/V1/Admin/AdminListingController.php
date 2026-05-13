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

    public function changeStatus(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== \App\Enums\UserRole::Admin) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $request->validate([
            'status' => 'required|string|in:ACTIVE,REJECTED,LOCKED',
            'rejection_reason' => 'nullable|string',
        ]);

        $status = $request->input('status');
        $rejectionReason = $request->input('rejection_reason');

        $listing = $this->listingService->changeStatusForAdmin($id, $status, $rejectionReason);

        return ApiResponse::success(
            data: new ListingResource($listing),
            message: 'Cập nhật trạng thái tin đăng thành công.'
        );
    }
}
