<?php

namespace App\Services\Impl;

use App\DTOs\Auth\AuthResultDto;
use App\DTOs\Auth\LoginCredentialsDto;
use App\DTOs\Auth\RegisterUserDto;
use App\Enums\OtpContext;
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
        private readonly UserRepository     $userRepository,
        private readonly AuthFactory        $authFactory,
        private readonly TokenProcessService $tokenProcessService,
        private readonly OtpService         $otpService,
    ) {}

    /** @throws AuthenticationFailedException */
    public function login(LoginCredentialsDto $dto): AuthResultDto
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = $this->authFactory->guard('api');
        $token = $guard->attempt(['email' => $dto->email, 'password' => $dto->password]);

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
     * Tạo user Pending + gửi OTP đăng ký.
     */
    public function register(RegisterUserDto $dto): void
    {
        DB::transaction(function () use ($dto) {
            $user = $this->userRepository->create([
                'full_name' => $dto->fullName,
                'email'     => $dto->email,
                'password'  => Hash::make($dto->password),
                'role'      => UserRole::User->value,
                'status'    => UserStatus::Pending->value,
            ]);

            Log::info('New user registered (pending OTP)', ['user_id' => $user->id]);

            $this->otpService->generate($user, OtpContext::REGISTER);
        });
    }

    public function resendRegisterOtp(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        
        // Nếu user không tồn tại hoặc đã Active thì không cho resend OTP đăng ký (bảo mật)
        if (!$user || $user->status !== UserStatus::Pending->value) {
            Log::warning('Resend register OTP failed (user not found or active)', ['email' => $email]);
            return;
        }

        $this->otpService->generate($user, OtpContext::REGISTER);
        Log::info('Register OTP resent', ['user_id' => $user->id]);
    }

    /**
     * Xác thực OTP đăng ký → Active user → trả token.
     *
     * @throws OtpInvalidException
     */
    public function verifyOtp(string $email, string $otp): AuthResultDto
    {
        /** @var User|null $user */
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$this->otpService->verify($user, $otp, OtpContext::REGISTER)) {
            throw new OtpInvalidException();
        }

        $this->userRepository->update($user->id, ['status' => UserStatus::Active->value]);
        $user->refresh();

        Log::info('User OTP verified, account activated', ['user_id' => $user->id]);
        UserRegistered::dispatch($user);

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = $this->authFactory->guard('api');
        $token = $guard->login($user);

        return AuthResultDto::fromUserAndToken($user, $token, $guard->factory()->getTTL());
    }

    /**
     * Quên mật khẩu: tìm user → sinh OTP reset → gửi mail.
     */
    public function forgotPassword(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            Log::warning('Forgot password for unknown email', ['email' => $email]);
            return;
        }

        $this->otpService->generate($user, OtpContext::RESET_PASSWORD);
        Log::info('Password reset OTP sent', ['user_id' => $user->id]);
    }

    /**
     * Bước 2: Kiểm tra OTP hợp lệ mà KHÔNG xóa Redis.
     * Sau khi pass bước này, user mới được vào bước đặt mật khẩu.
     *
     * @throws OtpInvalidException
     */
    public function checkResetOtp(string $email, string $otp): void
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$this->otpService->peek($user, $otp, OtpContext::RESET_PASSWORD)) {
            throw new OtpInvalidException();
        }
    }

    /**
     * Đặt lại mật khẩu: xác thực OTP → update password.
     *
     * @throws OtpInvalidException
     */
    public function resetPassword(string $email, string $otp, string $password): void
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$this->otpService->verify($user, $otp, OtpContext::RESET_PASSWORD)) {
            throw new OtpInvalidException();
        }

        $this->userRepository->update($user->id, [
            'password' => Hash::make($password),
        ]);

        Log::info('Password reset successfully', ['user_id' => $user->id]);
    }

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

    public function refresh(): string
    {
        $this->processToken();
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = $this->authFactory->guard('api');
        return $guard->refresh();
    }

    public function me(): ?User
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        return $this->authFactory->guard('api')->user();
    }

    public function processToken(): void
    {
        $token = JWTAuth::getToken();
        if ($token) {
            $payload = JWTAuth::getPayload($token);
            $ttl     = max(0, $payload->get('exp') - time());
            if ($ttl > 0) {
                $this->tokenProcessService->addTokenToBlacklist((string) $token, $ttl);
            }
        }
    }
}
