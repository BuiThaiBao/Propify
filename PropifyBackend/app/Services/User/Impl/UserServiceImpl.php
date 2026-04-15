<?php

namespace App\Services\User\Impl;

use App\DTOs\User\ChangePasswordDto;
use App\DTOs\User\UpdateProfileDto;
use App\Exceptions\AuthenticationFailedException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\User\UserService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class UserServiceImpl implements UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuthFactory $authFactory
    ) {
    }
    public function getProfile(): User
    {
        return $this->authFactory->guard('api')->user();
    }
    public function updateProfile(UpdateProfileDto $dto): User
    {
        /** @var User $user */
        $user = $this->authFactory->guard('api')->user();

        $data = ['full_name' => $dto->fullName];

        // Chỉ cho phép cập nhật SĐT nếu user chưa có SĐT
        if ($dto->phone !== null && empty($user->phone)) {
            $data['phone'] = $dto->phone;
        }

        // Cập nhật avatar_url nếu có
        if ($dto->avatarUrl !== null) {
            $data['avatar_url'] = $dto->avatarUrl;
        }

        $updated = $this->userRepository->update($user->id, $data);

        Log::info('User profile updated', ['user_id' => $user->id]);

        return $updated;
    }
    public function changePassword(ChangePasswordDto $dto): void
    {
        /** @var User $user */
        $user = $this->authFactory->guard('api')->user();

        // Kiểm tra mật khẩu hiện tại
        if (!Hash::check($dto->currentPassword, $user->password)) {
            throw new AuthenticationFailedException('Mật khẩu hiện tại không đúng.');
        }

        $this->userRepository->update($user->id, [
            'password' => Hash::make($dto->newPassword),
        ]);

        Log::info('User password changed', ['user_id' => $user->id]);
    }
}