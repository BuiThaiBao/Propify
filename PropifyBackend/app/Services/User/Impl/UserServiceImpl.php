<?php

namespace App\Services\User\Impl;

use App\Commands\User\ChangeUserPasswordCommand;
use App\Commands\User\UpdateUserProfileCommand;
use App\DTOs\User\ChangePasswordDto;
use App\DTOs\User\UpdateProfileDto;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

final class UserServiceImpl implements UserService
{
    public function __construct(
        private readonly AuthFactory $authFactory,
        private readonly UpdateUserProfileCommand $updateUserProfileCommand,
        private readonly ChangeUserPasswordCommand $changeUserPasswordCommand,
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

        $this->changeUserPasswordCommand->execute($user, $dto);
    }
}
