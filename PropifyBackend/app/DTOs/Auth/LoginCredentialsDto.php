<?php

namespace App\DTOs\Auth;

use Illuminate\Foundation\Http\FormRequest;

final readonly class LoginCredentialsDto
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $data = $request->validated();
        
        return new self(
            email: $data['email'],
            password: $data['password'],
        );
    }
}
