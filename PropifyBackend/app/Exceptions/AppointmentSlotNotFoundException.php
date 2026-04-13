<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class AppointmentSlotNotFoundException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(ErrorCode::AppointmentSlotNotFound);
    }
}
