<?php

namespace App\DTOs\Auth;

use App\Models\User;

final readonly class AuthResultDto
{
    public function __construct(
        public int $userId,
        public string $fullName,
        public string $role,
        public string $accessToken,
        public string $refreshToken,
        public string $tokenType,
        public int $expiresIn,
    ) {
    }

    public static function fromUserAndToken(User $user, string $accessToken, string $refreshToken, int $expiresInMinutes): self
    {
        return new self(
            userId: $user->id,
            fullName: $user->full_name ?? '',
            role: $user->role?->value ?? (is_string($user->role) ? $user->role : ''),
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            tokenType: 'bearer',
            expiresIn: $expiresInMinutes * 60,
        );
    }
}
