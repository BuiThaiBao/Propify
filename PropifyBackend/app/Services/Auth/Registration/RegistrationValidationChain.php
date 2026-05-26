<?php

namespace App\Services\Auth\Registration;

use App\DTOs\Auth\RegisterUserDto;
use App\Enums\ErrorCode;
use App\Enums\UserStatus;
use App\Exceptions\BusinessException;
use App\Repositories\UserRepository;

final class RegistrationValidationChain
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function validate(RegisterUserDto $dto): void
    {
        if (!filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            throw new BusinessException(ErrorCode::ValidationError, 'Email không hợp lệ.');
        }

        if (mb_strlen($dto->password) < 8) {
            throw new BusinessException(ErrorCode::ValidationError, 'Mật khẩu phải có ít nhất 8 ký tự.');
        }

        $existing = $this->userRepository->findByEmail($dto->email);
        if ($existing && $existing->status === UserStatus::Active) {
            throw new BusinessException(ErrorCode::UserAlreadyExists);
        }
    }
}
