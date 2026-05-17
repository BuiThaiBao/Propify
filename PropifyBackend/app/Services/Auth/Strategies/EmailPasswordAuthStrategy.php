<?php

namespace App\Services\Auth\Strategies;

use App\DTOs\Auth\AuthPayload;
use App\DTOs\Auth\AuthResultDto;
use App\DTOs\Auth\EmailPasswordAuthPayload;
use App\Enums\AuthMethod;
use App\Enums\ErrorCode;
use App\Enums\UserStatus;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Services\Auth\AuthStrategy;
use App\Services\Auth\AuthTokenIssuer;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class EmailPasswordAuthStrategy implements AuthStrategy
{
    public function __construct(
        private readonly AuthFactory $authFactory,
        private readonly AuthTokenIssuer $tokenIssuer,
    ) {
    }

    public function method(): AuthMethod
    {
        return AuthMethod::EmailPassword;
    }

    public function authenticate(AuthPayload $payload): AuthResultDto
    {
        if (!$payload instanceof EmailPasswordAuthPayload) {
            throw new InvalidArgumentException('EmailPasswordAuthStrategy requires EmailPasswordAuthPayload.');
        }

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = $this->authFactory->guard('api');
        $credentials = $payload->credentials;
        $token = $guard->attempt([
            'email' => $credentials->email,
            'password' => $credentials->password,
        ]);

        if (!$token) {
            Log::warning('Failed login attempt', ['email' => $credentials->email]);
            throw new BusinessException(ErrorCode::AuthLoginFailed);
        }

        /** @var User $user */
        $user = $guard->user();

        if ($user->status !== UserStatus::Active) {
            $guard->logout();
            Log::warning('Login attempt by non-active user', [
                'user_id' => $user->id,
                'status' => $user->status?->value,
            ]);
            throw new BusinessException(ErrorCode::AuthNotVerified);
        }

        Log::info('User logged in', ['user_id' => $user->id]);

        return $this->tokenIssuer->issueFromAccessToken(
            $user,
            $token,
            $guard->factory()->getTTL(),
        );
    }
}
