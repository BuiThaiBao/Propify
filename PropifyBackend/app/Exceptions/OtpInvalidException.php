<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

/** Ném khi OTP sai hoặc không tồn tại trong Redis (hết hạn) */
final class OtpInvalidException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(errorCode: ErrorCode::AuthOtpInvalid);
    }
}
