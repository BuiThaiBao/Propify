<?php

namespace App\Services\Auth;

use App\DTOs\Auth\AuthResultDto;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWTGuard;

final class AuthTokenIssuer
{
    public function __construct(
        private readonly AuthFactory $authFactory,
    ) {}

    public function issueFor(User $user): AuthResultDto
    {
        /** @var JWTGuard $guard */
        $guard = $this->authFactory->guard('api');
        $accessToken = $guard->login($user);

        return AuthResultDto::fromUserAndToken(
            $user,
            $accessToken,
            $this->makeRefreshToken($user),
            $guard->factory()->getTTL(),
        );
    }

    public function issueFromAccessToken(User $user, string $accessToken, int $expiresInMinutes): AuthResultDto
    {
        return AuthResultDto::fromUserAndToken(
            $user,
            $accessToken,
            $this->makeRefreshToken($user),
            $expiresInMinutes,
        );
    }

    private function makeRefreshToken(User $user): string
    {
        $factory = JWTAuth::factory();
        $originalTtl = $factory->getTTL();

        try {
            $factory->setTTL((int) config('jwt.refresh_ttl', 20160));

            return JWTAuth::claims(['typ' => 'refresh'])->fromUser($user);
        } finally {
            $factory->setTTL($originalTtl);
        }
    }
}
