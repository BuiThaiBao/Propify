<?php

namespace App\Http\Controllers\Api\V1\Amenity;

use App\Helpers\ApiResponse;
use App\Http\Requests\Amenity\StoreAmenityRequest;
use App\Http\Requests\Amenity\UpdateAmenityRequest;
use App\Http\Resources\AmenityResource;
use App\Services\Amenity\AmenityService;
use Illuminate\Http\JsonResponse;

final class AmenityController
{
    public function __construct(
        private readonly AmenityService $amenityService,
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(
            data: AmenityResource::collection($this->amenityService->getAll()),
            message: 'Lấy danh sách tiện ích thành công.',
        );
    }

    public function store(StoreAmenityRequest $request): JsonResponse
    {
        return ApiResponse::created(
            data: new AmenityResource($this->amenityService->create($request->toDto())),
            message: 'Tạo tiện ích thành công.',
        );
    }

    public function update(UpdateAmenityRequest $request, int $id): JsonResponse
    {
        return ApiResponse::success(
            data: new AmenityResource($this->amenityService->update($id, $request->toDto())),
            message: 'Cập nhật tiện ích thành công.',
        );
    }
}
