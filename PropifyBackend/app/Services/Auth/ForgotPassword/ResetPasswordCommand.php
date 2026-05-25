<?php

namespace App\Services\Auth\ForgotPassword;

use App\Enums\ErrorCode;
use App\Enums\OtpContext;
use App\Events\Auth\PasswordReset;
use App\Exceptions\BusinessException;
use App\Repositories\UserRepository;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class ResetPasswordCommand
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly OtpService $otpService,
    ) {}

    public function execute(string $email, string $otp, string $password): void
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user || !$this->otpService->verify($user, $otp, OtpContext::RESET_PASSWORD)) {
            throw new BusinessException(ErrorCode::AuthOtpInvalid);
        }

        $this->userRepository->update($user->id, [
            'password' => Hash::make($password),
        ]);

        event(new PasswordReset($user->id));
        Log::info('Password reset successfully', ['user_id' => $user->id]);
    }
}
