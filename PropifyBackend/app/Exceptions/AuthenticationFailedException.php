<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class AuthenticationFailedException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(ErrorCode::AuthLoginFailed);
    }
}
