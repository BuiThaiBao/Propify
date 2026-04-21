<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class BookingSelfSlotException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(ErrorCode::BookingSelfSlot);
    }
}
