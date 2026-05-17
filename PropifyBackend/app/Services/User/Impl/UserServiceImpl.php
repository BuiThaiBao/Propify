<?php

namespace App\Services\User\Impl;

use App\Commands\User\UpdateUserProfileCommand;
use App\DTOs\User\ChangePasswordDto;
use App\DTOs\User\UpdateProfileDto;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
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
        private readonly AuthFactory $authFactory,
        private readonly UpdateUserProfileCommand $updateUserProfileCommand,
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

        return $this->updateUserProfileCommand->execute($user, $dto);
    }
    public function changePassword(ChangePasswordDto $dto): void
    {
        /** @var User $user */
        $user = $this->authFactory->guard('api')->user();

        // Kiểm tra mật khẩu hiện tại
        if (!Hash::check($dto->currentPassword, $user->password)) {
            throw new BusinessException(ErrorCode::AuthPasswordIncorrect);
        }

        $this->userRepository->update($user->id, [
            'password' => Hash::make($dto->newPassword),
        ]);

        Log::info('User password changed', ['user_id' => $user->id]);
    }
}
