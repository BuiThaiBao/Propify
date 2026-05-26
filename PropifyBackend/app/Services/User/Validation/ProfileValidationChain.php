<?php

namespace App\Services\User\Validation;

use App\DTOs\User\UpdateProfileDto;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

final class ProfileValidationChain
{
    public function validate(UpdateProfileDto $dto): void
    {
        if (trim($dto->fullName) === '') {
            throw new BusinessException(ErrorCode::ValidationError, 'Họ tên không được để trống.');
        }

        if ($dto->phone !== null && !preg_match('/^[0-9]{9,12}$/', $dto->phone)) {
            throw new BusinessException(ErrorCode::ValidationError, 'Số điện thoại không hợp lệ.');
        }
    }
}
