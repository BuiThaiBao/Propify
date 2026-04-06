<?php

namespace App\Services\Impl;

use App\DTOs\Auth\AuthResultDto;
use App\DTOs\Auth\LoginCredentialsDto;
use App\DTOs\Auth\RegisterUserDto;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Events\Auth\UserRegistered;
use App\Exceptions\AuthenticationFailedException;
use App\Exceptions\OtpInvalidException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\Otp\OtpService;
use App\Services\TokenProcessService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

final class AuthServiceImpl implements AuthService
{
    public function __construct(
        private readonly UserRepository    $userRepository,
        private readonly AuthFactory       $authFactory,
        private readonly TokenProcessService $tokenProcessService,
        private readonly OtpService        $otpService,
    ) {}

    /**
     * Authenticate a user and return a token payload.
     *
     * @throws AuthenticationFailedException
     */
    public function login(LoginCredentialsDto $dto): AuthResultDto
    {
        $credentials = [
            'email'    => $dto->email,
            'password' => $dto->password,
        ];

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = $this->authFactory->guard('api');
        $token = $guard->attempt($credentials);

        if (!$token) {
            Log::warning('Failed login attempt', ['email' => $dto->email]);
            throw new AuthenticationFailedException();
        }

        /** @var User $user */
        $user = $guard->user();

        Log::info('User logged in', ['user_id' => $user->id]);

        return AuthResultDto::fromUserAndToken($user, $token, $guard->factory()->getTTL());
    }

    /**
     * Register: tạo user status=Pending, sinh OTP, gửi mail.
     * KHÔNG trả token — client phải verify OTP trước.
     */
    public function register(RegisterUserDto $dto): void
    {
        DB::transaction(function () use ($dto) {
            $user = $this->userRepository->create([
                'full_name' => $dto->fullName,
                'email'     => $dto->email,
                'password'  => Hash::make($dto->password),
                'role'      => UserRole::User->value,
                'status'    => UserStatus::Pending->value,  // Chưa active
            ]);

            Log::info('New user registered (pending OTP)', ['user_id' => $user->id]);

            // Sinh OTP → lưu Redis 3 phút → gửi email
            $this->otpService->generate($user);
        });
    }

    /**
     * Verify OTP → activate user → trả token.
     *
     * @throws OtpInvalidException
     */
    public function verifyOtp(string $email, string $otp): AuthResultDto
    {
        /** @var User|null $user */
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$this->otpService->verify($user, $otp)) {
            throw new OtpInvalidException();
        }

        // Kích hoạt tài khoản
        $this->userRepository->update($user->id, [
            'status' => UserStatus::Active->value,
        ]);

        $user->refresh();

        Log::info('User OTP verified, account activated', ['user_id' => $user->id]);

        // Fire welcome event
        UserRegistered::dispatch($user);

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = $this->authFactory->guard('api');
        $token = $guard->login($user);

        return AuthResultDto::fromUserAndToken($user, $token, $guard->factory()->getTTL());
    }

    /**
     * Invalidate the current token.
     */
    public function logout(): void
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = $this->authFactory->guard('api');

        /** @var User $user */
        $user = $guard->user();
        $this->processToken();

        Log::info('User logged out', ['user_id' => $user?->id]);

        $guard->logout();
    }

    /**
     * Refresh the current token.
     */
    public function refresh(): string
    {
        $this->processToken();

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = $this->authFactory->guard('api');

        /** @var string $newToken */
        $newToken = $guard->refresh();

        return $newToken;
    }

    /**
     * Get the authenticated user.
     */
    public function me(): ?User
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = $this->authFactory->guard('api');

        /** @var ?User $user */
        return $guard->user();
    }

    /**
     * Process the current token for blacklisting before refresh/logout.
     */
    public function processToken(): void
    {
        $token = JWTAuth::getToken();

        if ($token) {
            $payload = JWTAuth::getPayload($token);
            $exp     = $payload->get('exp');
            $ttl     = max(0, $exp - time());

            if ($ttl <= 0) {
                return;
            }

            $this->tokenProcessService->addTokenToBlacklist((string) $token, $ttl);
        }
    }
}
