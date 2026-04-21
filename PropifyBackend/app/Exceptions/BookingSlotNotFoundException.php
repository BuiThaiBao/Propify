<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class BookingSlotNotFoundException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(ErrorCode::BookingSlotNotFound);
    }
}
