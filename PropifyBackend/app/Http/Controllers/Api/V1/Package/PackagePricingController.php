<?php

namespace App\Http\Controllers\Api\V1\Package;

use App\Helpers\ApiResponse;
use App\Models\Package;
use App\Models\PackagePricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PackagePricingController
{
    /**
     * GET /api/v1/packages/{packageId}/pricings
     * Lấy danh sách pricing options cho package
     */
    public function index(int $packageId): JsonResponse
    {
        $package = Package::findOrFail($packageId);
        $pricings = $package->pricings()
            ->orderBy('duration_days')
            ->get();

        return ApiResponse::success(
            data: $pricings,
            message: 'Lấy danh sách pricing thành công.'
        );
    }

    /**
     * POST /api/v1/packages/{packageId}/pricings
     * Tạo pricing option mới
     */
    public function store(Request $request, int $packageId): JsonResponse
    {
        $package = Package::findOrFail($packageId);

        $validated = $request->validate([
            'duration_days' => ['required', 'integer', 'in:3,7,10,15,30'],
            'price'         => ['required', 'numeric', 'min:0'],
            'label'         => ['required', 'string', 'max:50'],
            'is_active'     => ['sometimes', 'boolean'],
        ], [
            'duration_days.required' => 'Vui lòng chọn số ngày.',
            'duration_days.in'       => 'Số ngày phải là 3, 7, 10, 15 hoặc 30.',
            'price.required'         => 'Vui lòng nhập giá.',
            'price.min'              => 'Giá phải lớn hơn hoặc bằng 0.',
            'label.required'         => 'Vui lòng nhập nhãn hiển thị.',
        ]);

        // Check duplicate
        $exists = $package->pricings()
            ->where('duration_days', $validated['duration_days'])
            ->exists();

        if ($exists) {
            return ApiResponse::error(
                message: "Đã tồn tại pricing cho {$validated['duration_days']} ngày.",
                statusCode: 409,
            );
        }

        $pricing = $package->pricings()->create($validated);

        return ApiResponse::created(
            data: $pricing,
            message: 'Tạo pricing thành công.'
        );
    }

    /**
     * PUT /api/v1/packages/{packageId}/pricings/{pricingId}
     * Cập nhật pricing option
     */
    public function update(Request $request, int $packageId, int $pricingId): JsonResponse
    {
        $pricing = PackagePricing::where('package_id', $packageId)
            ->findOrFail($pricingId);

        $validated = $request->validate([
            'price'     => ['sometimes', 'numeric', 'min:0'],
            'label'     => ['sometimes', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $pricing->update($validated);

        return ApiResponse::success(
            data: $pricing->fresh(),
            message: 'Cập nhật pricing thành công.'
        );
    }

    /**
     * DELETE /api/v1/packages/{packageId}/pricings/{pricingId}
     * Xóa pricing option
     */
    public function destroy(int $packageId, int $pricingId): JsonResponse
    {
        $pricing = PackagePricing::where('package_id', $packageId)
            ->findOrFail($pricingId);

        $pricing->delete();

        return ApiResponse::success(
            message: 'Xóa pricing thành công.'
        );
    }
}
