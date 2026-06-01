<?php

namespace App\Services\Auth\ForgotPassword;

use App\DTOs\Auth\ForgotPasswordContext;

final class ForgotPasswordChain
{
    public function __construct(
        private readonly ForgotPasswordHandler $firstHandler,
    ) {}

    public function execute(string $email): void
    {
        $this->firstHandler->handle(new ForgotPasswordContext($email));
    }
}
