<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use RuntimeException;
use Throwable;

abstract class BusinessException extends RuntimeException
{
    public function __construct(
        protected readonly ErrorCode $errorCode,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $errorCode->message(),
            code: $errorCode->httpStatus(),
            previous: $previous
        );
    }

    public function getErrorCode(): ErrorCode
    {
        return $this->errorCode;
    }
}
