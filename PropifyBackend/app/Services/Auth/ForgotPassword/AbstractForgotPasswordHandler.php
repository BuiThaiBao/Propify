<?php

namespace App\Services\Auth\ForgotPassword;

use App\DTOs\Auth\ForgotPasswordContext;

abstract class AbstractForgotPasswordHandler implements ForgotPasswordHandler
{
    private ?ForgotPasswordHandler $next = null;

    public function setNext(ForgotPasswordHandler $handler): ForgotPasswordHandler
    {
        $this->next = $handler;

        return $handler;
    }

    protected function next(ForgotPasswordContext $context): void
    {
        $this->next?->handle($context);
    }
}
