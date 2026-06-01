<?php

namespace App\DTOs\Auth;

use App\Models\User;

final class ForgotPasswordContext
{
    public function __construct(
        public readonly string $email,
        public ?User $user = null,
    ) {}
}
