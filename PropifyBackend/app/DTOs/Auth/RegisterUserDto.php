<?php

namespace App\DTOs\Auth;

use Illuminate\Foundation\Http\FormRequest;

final readonly class RegisterUserDto
{
    public function __construct(
        public string $fullName,
        public string $email,
        public string $password,
    ) {
    }

    public static function fromRequest(FormRequest $request): self
    {
        $data = $request->validated();

        return new self(
            fullName: $data['full_name'],
            email: $data['email'],
            password: $data['password'],
        );
    }
}
