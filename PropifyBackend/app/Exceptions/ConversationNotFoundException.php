<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

final class ConversationNotFoundException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(ErrorCode::ConversationNotFound);
    }
}
