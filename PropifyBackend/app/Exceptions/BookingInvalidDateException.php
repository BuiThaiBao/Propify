<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Throwable;

class BookingInvalidDateException extends BusinessException
{
    public function __construct(string $customMessage = '', ?Throwable $previous = null)
    {
        parent::__construct(ErrorCode::BookingInvalidDate, $previous);

        if ($customMessage !== '') {
            $this->message = $customMessage;
        }
    }
}
