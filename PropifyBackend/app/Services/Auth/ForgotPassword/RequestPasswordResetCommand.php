<?php

namespace App\Services\Auth\ForgotPassword;

final class RequestPasswordResetCommand
{
    public function __construct(
        private readonly ForgotPasswordChain $forgotPasswordChain,
    ) {}

    public function execute(string $email): void
    {
        $this->forgotPasswordChain->execute($email);
    }
}
