<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use RuntimeException;
use Throwable;

final class BusinessException extends RuntimeException
{
    public function __construct(
        protected readonly ErrorCode $errorCode,
        string|Throwable|null $messageOrPrevious = null,
        ?Throwable $previous = null,
    ) {
        if ($messageOrPrevious instanceof Throwable) {
            $previous = $messageOrPrevious;
            $message = $errorCode->message();
        } else {
            $message = $messageOrPrevious ?: $errorCode->message();
        }

        parent::__construct(
            message: $message,
            code: $errorCode->httpStatus(),
            previous: $previous
        );
    }

    public function getErrorCode(): ErrorCode
    {
        return $this->errorCode;
    }
}
