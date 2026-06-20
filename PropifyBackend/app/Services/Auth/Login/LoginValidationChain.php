<?php

namespace App\Services\Auth\Login;

use App\Enums\ErrorCode;
use App\Enums\UserStatus;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

final class LoginValidationChain
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function validate(string $email, string $password): User
    {
        $user = $this->userRepository->findByEmail($email);
        if (! $user) {
            throw new BusinessException(ErrorCode::AuthLoginFailed);
        }

        if ($user->status !== UserStatus::Active) {
            throw new BusinessException(ErrorCode::AuthNotVerified);
        }

        if (is_null($user->password)) {
            throw new BusinessException(ErrorCode::AuthLoginFailed);
        }

        if (! Hash::check($password, $user->password)) {
            throw new BusinessException(ErrorCode::AuthLoginFailed);
        }

        return $user;
    }
}
