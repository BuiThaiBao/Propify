<?php

namespace App\Services\User\Validation;

use App\DTOs\User\ChangePasswordDto;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class PasswordValidationChain
{
    public function validate(User $user, ChangePasswordDto $dto): void
    {
        if (! Hash::check($dto->currentPassword, $user->password)) {
            throw new BusinessException(ErrorCode::AuthPasswordIncorrect);
        }

        if (mb_strlen($dto->newPassword) < 8) {
            throw new BusinessException(ErrorCode::ValidationError, 'Mật khẩu mới phải có ít nhất 8 ký tự.');
        }
    }
}
