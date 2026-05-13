<?php

namespace App\Http\Controllers\Api\V1\Package;

use App\Helpers\ApiResponse;
use App\Enums\UserRole;
use App\Models\PackageDurationOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PackageDurationOptionController
{
    public function index(): JsonResponse
    {
        $durations = PackageDurationOption::active()
            ->orderBy('days')
            ->get();

        return ApiResponse::success(
            data: $durations,
            message: 'Lấy danh sách thời hạn thành công.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === UserRole::Admin, 403);

        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:3650', 'unique:package_duration_options,days'],
            'label' => ['nullable', 'string', 'max:50'],
        ]);

        $days = (int) $validated['days'];
        $duration = PackageDurationOption::create([
            'days' => $days,
            'label' => $validated['label'] ?? "{$days} ngày",
            'is_active' => true,
        ]);

        return ApiResponse::created(
            data: $duration,
            message: 'Tạo thời hạn thành công.'
        );
    }
}
