<?php

namespace App\Services;

use App\DTOs\Auth\AuthResultDto;
use App\DTOs\Auth\LoginCredentialsDto;
use App\DTOs\Auth\RegisterUserDto;
use App\Exceptions\AuthenticationFailedException;
use App\Exceptions\OtpInvalidException;
use App\Models\User;

interface AuthService
{
    /**
     * Authenticate a user and return a token payload.
     *
     * @throws AuthenticationFailedException
     */
    public function login(LoginCredentialsDto $dto): AuthResultDto;

    /**
     * Register a new user (status = Pending), generate & send OTP.
     * Returns void — chưa cấp token, phải verify OTP trước.
     */
    public function register(RegisterUserDto $dto): void;

    /**
     * Verify OTP, activate user (status = Active), return token.
     *
     * @throws OtpInvalidException
     * @throws AuthenticationFailedException
     */
    public function verifyOtp(string $email, string $otp): AuthResultDto;

    /**
     * Invalidate the current token.
     */
    public function logout(): void;

    /**
     * Refresh the current token.
     */
    public function refresh(): string;

    /**
     * Get the authenticated user.
     */
    public function me(): ?User;

    /**
     * Process the current token for blacklisting.
     */
    public function processToken(): void;
}
