<?php

namespace App\Services\Auth\Strategies;

use App\DTOs\Auth\AuthPayload;
use App\DTOs\Auth\AuthResultDto;
use App\DTOs\Auth\EmailPasswordAuthPayload;
use App\Enums\AuthMethod;
use App\Events\Auth\UserLoggedIn;
use App\Services\Auth\Login\LoginValidationChain;
use App\Services\Auth\AuthStrategy;
use App\Services\Auth\AuthTokenIssuer;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use InvalidArgumentException;

final class EmailPasswordAuthStrategy implements AuthStrategy
{
    public function __construct(
        private readonly AuthFactory $authFactory,
        private readonly AuthTokenIssuer $tokenIssuer,
        private readonly LoginValidationChain $loginValidationChain,
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

        $credentials = $payload->credentials;
        $this->loginValidationChain->validate($credentials->email, $credentials->password);

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = $this->authFactory->guard('api');
        $token = $guard->attempt(['email' => $credentials->email, 'password' => $credentials->password]);
        /** @var User $user */
        $user = $guard->user();
        event(new UserLoggedIn($user->id));

        return $this->tokenIssuer->issueFromAccessToken(
            $user,
            $token,
            $guard->factory()->getTTL(),
        );
    }
}
