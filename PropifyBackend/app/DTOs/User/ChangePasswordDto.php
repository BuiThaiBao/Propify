<?php

namespace App\DTOs\User;

use Illuminate\Http\Request;

final readonly class ChangePasswordDto
{
    public function __construct(
        public string $currentPassword,
        public string $newPassword,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            currentPassword: $request->input('current_password'),
            newPassword: $request->input('new_password')
        );
    }
}