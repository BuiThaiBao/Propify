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
     * GET /v1/users/search?phone=012345
     * Tìm user theo số điện thoại (partial match, LIKE).
     * Trả về tối đa 5 kết quả, loại trừ chính mình.
     */
    public function searchByPhone(Request $request): JsonResponse
    {
        $phone = $request->query('phone');
        $digits = preg_replace('/\D/', '', $phone ?? '');

        if (strlen($digits) < 3) {
            return ApiResponse::success(data: [], message: 'Nhập ít nhất 3 số.');
        }

        $users = User::where('phone', 'LIKE', "%{$digits}%")
            ->where('id', '!=', auth()->id())
            ->limit(5)
            ->get(['id', 'full_name', 'phone', 'avatar_url']);

        return ApiResponse::success(
            data: UserResource::collection($users),
            message: 'Kết quả tìm kiếm.',
        );
    }
}
