<?php

namespace App\DTOs\Auth;

use App\Models\Users;

final readonly class AuthResultDto
{
    public function __construct(
        public int $userId,
        public string $fullName,
        public string $role,
        public string $accessToken,
        public string $tokenType,
        public int $expiresIn,
    ) {
    }

    public static function fromUserAndToken(Users $user, string $token, int $expiresInMinutes): self
    {
        return new self(
            userId: $user->id,
            fullName: $user->full_name ?? '',
            role: $user->role?->value ?? (is_string($user->role) ? $user->role : ''),
            accessToken: $token,
            tokenType: 'bearer',
            expiresIn: $expiresInMinutes * 60, // Convert to seconds
        );
    }
}
