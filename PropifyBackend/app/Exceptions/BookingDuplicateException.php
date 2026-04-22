<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class BookingDuplicateException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(ErrorCode::BookingDuplicate);
    }
}
