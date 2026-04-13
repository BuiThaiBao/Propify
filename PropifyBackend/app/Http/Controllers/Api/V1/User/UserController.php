<?php

namespace App\Http\Controllers\Api\V1\User;


use App\Helpers\ApiResponse;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;

final class UserController
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }
    public function getProfile(): JsonResponse
    {
        $user = $this->userService->getProfile();
        return ApiResponse::success(
            data: new UserResource($user)
        );
    }
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->userService->updateProfile($request->toDto());

        return ApiResponse::success(
            data: new UserResource($user),
            message: 'Cập nhật thông tin thành công.',
        );
    }
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->userService->changePassword($request->toDto());

        return ApiResponse::success(
            message: 'Đổi mật khẩu thành công.',
        );
    }
}