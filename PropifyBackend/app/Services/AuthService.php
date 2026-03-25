<?php

namespace App\Services;

use App\DTOs\Auth\AuthResultDto;
use App\DTOs\Auth\LoginCredentialsDto;
use App\DTOs\Auth\RegisterUserDto;
use App\Exceptions\AuthenticationFailedException;
use App\Models\Users;

interface AuthService
{
    /**
     * Authenticate a user and return a token payload.
     *
     * @throws AuthenticationFailedException
     */
    public function login(LoginCredentialsDto $dto): AuthResultDto;

    /**
     * Register a new user and return their token payload.
     */
    public function register(RegisterUserDto $dto): AuthResultDto;

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
    public function me(): ?Users;
}
