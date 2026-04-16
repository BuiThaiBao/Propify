<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

final class UnauthorizedConversationAccessException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(ErrorCode::UnauthorizedConversationAccess);
    }
}
