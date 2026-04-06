<?php

namespace App\Services\Impl;

use App\DTOs\Auth\AuthResultDto;
use App\DTOs\Auth\LoginCredentialsDto;
use App\DTOs\Auth\RegisterUserDto;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\AuthenticationFailedException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\TokenProcessService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

final class AuthServiceImpl implements AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuthFactory $authFactory,
        private readonly TokenProcessService $tokenProcessService
    ) {
    }

    /**
     * Authenticate a user and return a token payload.
     *
     * @throws AuthenticationFailedException
     */
    public function login(LoginCredentialsDto $dto): AuthResultDto
    {
        $credentials = [
            'email' => $dto->email,
            'password' => $dto->password,
        ];

        $token = $this->authFactory->guard('api')->attempt($credentials);

        if (!$token) {
            Log::warning('Failed login attempt', ['email' => $dto->email]);
            throw new AuthenticationFailedException();
        }

        /** @var User $user */
        $user = $this->authFactory->guard('api')->user();

        Log::info('User logged in', ['user_id' => $user->id]);

        return AuthResultDto::fromUserAndToken(
            $user,
            $token,
            $this->authFactory->guard('api')->factory()->getTTL()
        );
    }

    /**
     * Register a new user and return their token payload.
     * Uses a DB transaction to ensure data integrity.
     */
    public function register(RegisterUserDto $dto): AuthResultDto
    {
        return DB::transaction(function () use ($dto) {
            $user = $this->userRepository->create([
                'full_name' => $dto->fullName,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
                'role' => UserRole::User->value,
                'status' => UserStatus::Active->value,
            ]);

            Log::info('New user registered', ['user_id' => $user->id]);

            $token = $this->authFactory->guard('api')->login($user);

            return AuthResultDto::fromUserAndToken(
                $user,
                $token,
                $this->authFactory->guard('api')->factory()->getTTL()
            );
        });
    }

    /**
     * Invalidate the current token.
     */
    public function logout(): void
    {
        /** @var User $user */
        $user = $this->authFactory->guard('api')->user();
        $this->processToken();

        Log::info('User logged out', [
            'user_id' => $user?->id,
        ]);

        $this->authFactory->guard('api')->logout();
    }

    /**
     * Refresh the current token.
     */
    public function refresh(): string
    {
        $this->processToken();

        /** @var string $newToken */
        $newToken = $this->authFactory->guard('api')->refresh();

        return $newToken;
    }

    /**
     * Get the authenticated user.
     */
    public function me(): ?User
    {
        /** @var ?User $user */
        $user = $this->authFactory->guard('api')->user();

        return $user;
    }

    /**
     * Process the current token for blacklisting before refresh/logout.
     */
    public function processToken(): void
    {
        $token = JWTAuth::getToken();

        if ($token) {
            $payload = JWTAuth::getPayload($token);
            $exp = $payload->get('exp');
            $ttl = max(0, $exp - time());

            if ($ttl <= 0) {
                return;
            }

            $this->tokenProcessService->addTokenToBlacklist((string) $token, $ttl);
        }
    }
}
