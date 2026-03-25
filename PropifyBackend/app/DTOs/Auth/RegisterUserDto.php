<?php

namespace App\DTOs\Auth;

use Illuminate\Foundation\Http\FormRequest;

final readonly class RegisterUserDto
{
    public function __construct(
        public string $fullName,
        public string $phone,
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $data = $request->validated();
        
        return new self(
            fullName: $data['full_name'],
            phone: $data['phone'],
            email: $data['email'],
            password: $data['password'],
        );
    }
}
