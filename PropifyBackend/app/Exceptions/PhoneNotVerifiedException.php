<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

final class PhoneNotVerifiedException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(errorCode: ErrorCode::AuthPhoneNotVerified);
    }
}
