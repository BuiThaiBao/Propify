<?php

namespace App\Http\Controllers\Api\V1\Package;

use App\DTOs\Packages\CreatePackageDto;
use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use App\Http\Resources\Requests\Package\CreatePackageRequest;
use App\Services\Packages\PackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PackageController
{
    public function __construct(
        private readonly PackageService $packageService,
    ) {}

    public function create(CreatePackageRequest $request): JsonResponse
    {
        $dto = CreatePackageDto::fromRequest($request);
        $package = $this->packageService->create($dto);

        return ApiResponse::created(
            data: $package,
            message: 'Tạo gói tin thành công.'
        );
    }
    public function index(Request $request): JsonResponse
    {
        $includeInactive = $request->boolean('include_inactive')
            && $request->user('api')?->role === UserRole::Admin;

        $packages = $this->packageService->getAll(
            keyword: $request->input('keyword'),
            status: $request->input('status'),
            includeInactive: $includeInactive,
        );
        
        return ApiResponse::success(
            data: $packages,
            message: 'Lấy danh sách gói tin thành công.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $package = $this->packageService->getById($id);

        return ApiResponse::success(
            data: $package,
            message: 'Lấy thông tin gói tin thành công.'
        );
    }

    public function update(\App\Http\Resources\Requests\Package\UpdatePackageRequest $request, int $id): JsonResponse
    {
        $dto = \App\DTOs\Packages\UpdatePackageDto::fromRequest($request);
        $package = $this->packageService->update($id, $dto);

        return ApiResponse::success(
            data: $package,
            message: 'Cập nhật gói tin thành công.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->packageService->delete($id);

        return ApiResponse::success(
            data: null,
            message: 'Đã xóa (ẩn) gói tin thành công.'
        );
    }
}
