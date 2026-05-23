<?php

namespace App\Services\Auth\Impl;

use App\DTOs\Auth\AuthResultDto;
use App\DTOs\Auth\EmailPasswordAuthPayload;
use App\DTOs\Auth\LoginCredentialsDto;
use App\DTOs\Auth\RegisterUserDto;
use App\Enums\AuthMethod;
use App\Enums\OtpContext;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Events\Auth\UserRegistered;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;
use App\Services\Auth\AuthStrategyResolver;
use App\Services\Auth\AuthTokenIssuer;
use App\Services\Auth\ForgotPassword\ForgotPasswordChain;
use App\Services\Otp\OtpService;
use App\Services\Auth\TokenProcessService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

final class AuthServiceImpl implements AuthService
{
    public function __construct(
        private readonly UserRepository     $userRepository,
        private readonly AuthFactory        $authFactory,
        private readonly TokenProcessService $tokenProcessService,
        private readonly OtpService         $otpService,
        private readonly AuthStrategyResolver $authStrategyResolver,
        private readonly AuthTokenIssuer $tokenIssuer,
        private readonly ForgotPasswordChain $forgotPasswordChain,
    ) {}

    /** @throws BusinessException */
    public function login(LoginCredentialsDto $dto): AuthResultDto
    {
        return $this->authStrategyResolver
            ->resolve(AuthMethod::EmailPassword)
            ->authenticate(new EmailPasswordAuthPayload($dto));
    }

    /**
     * Tạo user Pending + gửi OTP đăng ký.
     */
    public function register(RegisterUserDto $dto): void
    {
        DB::transaction(function () use ($dto) {
            $user = $this->userRepository->findByEmail($dto->email);

            if ($user) {
                // User đang Pending (validate đã lọc), cập nhật lại thông tin mới nhất
                $this->userRepository->update($user->id, [
                    'full_name' => $dto->fullName,
                    'password'  => Hash::make($dto->password),
                ]);
                $user->refresh(); // Lấy data mới
                Log::info('Existing pending user re-registered', ['user_id' => $user->id]);
            } else {
                $user = $this->userRepository->create([
                    'full_name' => $dto->fullName,
                    'email'     => $dto->email,
                    'password'  => Hash::make($dto->password),
                    'role'      => UserRole::User->value,
                    'status'    => UserStatus::Pending->value,
                ]);
                Log::info('New user registered (pending OTP)', ['user_id' => $user->id]);
            }

            $this->otpService->generate($user, OtpContext::REGISTER);
        });
    }

    public function resendRegisterOtp(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        
        // Nếu user không tồn tại hoặc đã Active thì không cho resend OTP đăng ký (bảo mật)
        if (!$user || $user->status !== UserStatus::Pending) {
            Log::warning('Resend register OTP failed (user not found or active)', ['email' => $email]);
            return;
        }

        $this->otpService->generate($user, OtpContext::REGISTER);
        Log::info('Register OTP resent', ['user_id' => $user->id]);
    }

    /**
     * Xác thực OTP đăng ký → Active user → trả token.
     *
     * @throws BusinessException
     */
    public function verifyOtp(string $email, string $otp): AuthResultDto
    {
        /** @var User|null $user */
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$this->otpService->verify($user, $otp, OtpContext::REGISTER)) {
            throw new BusinessException(ErrorCode::AuthOtpInvalid);
        }

        $this->userRepository->update($user->id, ['status' => UserStatus::Active->value]);
        $user->refresh();

        Log::info('User OTP verified, account activated', ['user_id' => $user->id]);
        UserRegistered::dispatch($user);
 
        if ($user->role === UserRole::Admin) {
            Log::warning('Admin user attempted to verify OTP on client site', ['user_id' => $user->id]);
            throw new BusinessException(ErrorCode::AuthAdminNotAllowed);
        }

        return $this->tokenIssuer->issueFor($user);
    }

    /**
     * Quên mật khẩu: tìm user → sinh OTP reset → gửi mail.
     */
    public function forgotPassword(string $email): void
    {
        $this->forgotPasswordChain->execute($email);
    }

    /**
     * Bước 2: Kiểm tra OTP hợp lệ mà KHÔNG xóa Redis.
     * Sau khi pass bước này, user mới được vào bước đặt mật khẩu.
     *
     * @throws BusinessException
     */
    public function checkResetOtp(string $email, string $otp): void
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$this->otpService->peek($user, $otp, OtpContext::RESET_PASSWORD)) {
            throw new BusinessException(ErrorCode::AuthOtpInvalid);
        }
    }

    /**
     * Đặt lại mật khẩu: xác thực OTP → update password.
     *
     * @throws BusinessException
     */
    public function resetPassword(string $email, string $otp, string $password): void
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$this->otpService->verify($user, $otp, OtpContext::RESET_PASSWORD)) {
            throw new BusinessException(ErrorCode::AuthOtpInvalid);
        }

        $this->userRepository->update($user->id, [
            'password' => Hash::make($password),
        ]);

        Log::info('Password reset successfully', ['user_id' => $user->id]);
    }

    public function logout(?string $refreshToken = null): void
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = $this->authFactory->guard('api');
        /** @var User $user */
        $user = $guard->user();
        $this->processToken();
        $this->invalidateToken($refreshToken);
        Log::info('User logged out', ['user_id' => $user?->id]);
        $guard->logout();
    }

    public function refresh(string $refreshToken): AuthResultDto
    {
        try {
            $payload = JWTAuth::setToken($refreshToken)->getPayload();
            if ($payload->get('typ') !== 'refresh') {
                throw new JWTException('Invalid token type.');
            }

            /** @var User $user */
            $user = JWTAuth::setToken($refreshToken)->authenticate();
            if (!$user || $user->status !== UserStatus::Active) {
                throw new JWTException('Invalid refresh subject.');
            }

            $this->invalidateToken($refreshToken);

            return $this->tokenIssuer->issueFor($user);
        } catch (JWTException $e) {
            Log::warning('Refresh token failed', ['message' => $e->getMessage()]);
            throw new BusinessException(ErrorCode::AuthUnauthorized);
        }
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

    private function invalidateToken(?string $token): void
    {
        if (!$token) {
            return;
        }

        try {
            JWTAuth::setToken($token)->invalidate();
        } catch (JWTException $e) {
            Log::warning('Token invalidation failed', ['message' => $e->getMessage()]);
        }
    }
}
