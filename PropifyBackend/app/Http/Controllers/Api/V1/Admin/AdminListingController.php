<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ErrorCode;
use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use App\Helpers\ApiResponse;
use App\Http\Resources\AdminListingReportResource;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Services\Listing\ListingService;
use App\Support\ListingPostingOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class AdminListingController extends Controller
{
    public function __construct(
        private readonly ListingService $listingService,
    ) {}

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
        if ($request->user()->role !== UserRole::Admin) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $request->validate([
            'status' => ['required', 'string', Rule::in(ListingPostingOptions::values('admin_listing_statuses'))],
            'rejection_reason' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        $status = $request->input('status');
        $rejectionReason = $request->input('reason', $request->input('rejection_reason'));
        if ($status === 'REJECTED' && trim((string) $rejectionReason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'Vui lòng nhập lý do từ chối.',
            ]);
        }

        $listing = $this->listingService->changeStatusForAdmin(
            listingId: $id,
            status: $status,
            rejectionReason: $rejectionReason,
            adminUserId: (int) $request->user()->id,
        );

        return ApiResponse::success(
            data: new ListingResource($listing),
            message: 'Cập nhật trạng thái tin đăng thành công.'
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== UserRole::Admin) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $listing = $this->listingService->getListingDetailsForAdmin($id);

        return ApiResponse::success(
            data: new ListingResource($listing),
            message: 'Lấy chi tiết tin đăng thành công.'
        );
    }

    public function reports(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== UserRole::Admin) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $listing = Listing::query()->find($id);

        if ($listing === null) {
            return ApiResponse::notFound('Không tìm thấy tin đăng.');
        }

        $reports = $listing->reports()
            ->with('reporter')
            ->latest()
            ->get();

        $groupedReports = $reports
            ->groupBy(fn ($report) => $report->report_group_id ?: $this->legacyReportGroupKey($report))
            ->map(function ($items) {
                $first = $items->sortByDesc('created_at')->first();
                $oldest = $items->sortBy('created_at')->first();
                $latest = $items->sortByDesc('updated_at')->first();
                $reasons = $items->pluck('reason')->filter()->unique()->values();
                $imageUrls = collect($first->image_urls ?? [])
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'id' => $first->id,
                    'report_group_id' => $first->report_group_id,
                    'listing_id' => $first->listing_id,
                    'reporter' => $first->reporter,
                    'reasons' => $reasons,
                    'reason_labels' => $reasons->map(fn (string $reason) => $this->reportReasonLabel($reason))->values(),
                    'description' => $first->description,
                    'image_urls' => $imageUrls,
                    'status' => $first->status,
                    'report_count' => $items->count(),
                    'created_at' => $oldest->created_at,
                    'updated_at' => $latest->updated_at,
                ];
            })
            ->sortByDesc('created_at')
            ->values();

        return ApiResponse::success(
            data: AdminListingReportResource::collection($groupedReports),
            message: 'Lấy danh sách cảnh báo tin đăng thành công.',
            meta: [
                'total' => $groupedReports->count(),
            ]
        );
    }

    private function legacyReportGroupKey($report): string
    {
        $imageKey = md5(json_encode($report->image_urls ?? []));
        $descriptionKey = md5((string) $report->description);
        $createdMinute = $report->created_at?->format('Y-m-d H:i') ?? '';

        return implode('|', [
            'legacy',
            $report->listing_id,
            $report->reporter_id,
            $descriptionKey,
            $imageKey,
            $createdMinute,
        ]);
    }

    private function reportReasonLabel(string $reason): string
    {
        return match ($reason) {
            'WRONG_PRICE' => 'Định giá chưa đúng với thực tế',
            'WRONG_ADDRESS' => 'Địa chỉ của BĐS chưa chính xác',
            'SOLD_OR_RENTED' => 'BĐS đã bán/đã thuê/đã sang nhượng',
            'WRONG_INFORMATION' => 'Thông tin chưa chính xác',
            'UNREACHABLE_OWNER' => 'Không liên lạc được với người đăng tin',
            'DUPLICATE_LISTING' => 'Trùng với tin rao khác',
            default => $reason,
        };
    }

    public function updateVerification(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== UserRole::Admin) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $validated = $request->validate([
            'is_verified' => ['required', 'boolean'],
            'reason' => ['required_if:is_verified,false', 'nullable', 'string'],
        ]);

        $listing = $this->listingService->updateVerificationForAdmin(
            listingId: $id,
            isVerified: (bool) $validated['is_verified'],
            reason: $validated['reason'] ?? null,
            adminUserId: (int) $request->user()->id,
        );

        return ApiResponse::success(
            data: new ListingResource($listing),
            message: 'Cap nhat trang thai xac thuc thanh cong.'
        );
    }
}
