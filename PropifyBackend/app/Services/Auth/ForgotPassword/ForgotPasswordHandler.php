<?php

namespace App\Services\Auth\ForgotPassword;

use App\DTOs\Auth\ForgotPasswordContext;

interface ForgotPasswordHandler
{
    public function setNext(ForgotPasswordHandler $handler): ForgotPasswordHandler;

    public function handle(ForgotPasswordContext $context): void;
}
