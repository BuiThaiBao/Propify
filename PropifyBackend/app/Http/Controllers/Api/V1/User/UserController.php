<?php

namespace App\Http\Controllers\Api\V1\User;


use App\Helpers\ApiResponse;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    /**
     * GET /v1/users/search?phone=0123456789
     * Tìm user theo số điện thoại (chính xác) để bắt đầu cuộc trò chuyện.
     * Không trả về chính mình.
     */
    public function searchByPhone(Request $request): JsonResponse
    {
        $phone = $request->query('phone');

        if (!$phone || strlen(preg_replace('/\D/', '', $phone)) < 9) {
            return ApiResponse::error(message: 'Số điện thoại không hợp lệ.', status: 422);
        }

        $user = User::where('phone', $phone)
            ->where('id', '!=', auth()->id())
            ->first(['id', 'full_name', 'phone', 'avatar_url']);

        if (!$user) {
            return ApiResponse::error(message: 'Không tìm thấy người dùng với số điện thoại này.', status: 404);
        }

        return ApiResponse::success(
            data: new UserResource($user),
            message: 'Tìm thấy người dùng.',
        );
    }
}