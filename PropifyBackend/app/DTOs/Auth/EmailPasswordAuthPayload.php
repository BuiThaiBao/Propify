<?php

namespace App\DTOs\Auth;

final readonly class EmailPasswordAuthPayload implements AuthPayload
{
    public function __construct(
        public LoginCredentialsDto $credentials,
    ) {
    }
}
