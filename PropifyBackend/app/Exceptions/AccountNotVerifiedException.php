<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class AccountNotVerifiedException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(ErrorCode::AuthNotVerified);
    }
}
