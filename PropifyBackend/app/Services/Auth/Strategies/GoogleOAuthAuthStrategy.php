<?php

namespace App\Services\Auth\Strategies;

use App\DTOs\Auth\AuthPayload;
use App\DTOs\Auth\AuthResultDto;
use App\DTOs\Auth\SocialAuthPayload;
use App\Enums\AuthMethod;
use App\Enums\ErrorCode;
use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use App\Services\Auth\AuthStrategy;
use App\Services\Auth\AuthTokenIssuer;
use App\Services\Auth\UserUpsertService;
use InvalidArgumentException;

final class GoogleOAuthAuthStrategy implements AuthStrategy
{
    public function __construct(
        private readonly UserUpsertService $userUpsertService,
        private readonly AuthTokenIssuer $tokenIssuer,
    ) {
    }

    public function method(): AuthMethod
    {
        return AuthMethod::GoogleOAuth;
    }

    public function authenticate(AuthPayload $payload): AuthResultDto
    {
        if (!$payload instanceof SocialAuthPayload) {
            throw new InvalidArgumentException('GoogleOAuthAuthStrategy requires SocialAuthPayload.');
        }

        $user = $this->userUpsertService->upsertFromSocial($payload->socialUser);

        if ($user->role === UserRole::Admin) {
            throw new BusinessException(ErrorCode::AuthAdminNotAllowed);
        }

        return $this->tokenIssuer->issueFor($user);
    }
}
