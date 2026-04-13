<?php

namespace App\Services\User;

use App\DTOs\User\ChangePasswordDto;
use App\DTOs\User\UpdateProfileDto;
use App\Models\User;

interface UserService
{
    public function getProfile(): User;
    public function updateProfile(UpdateProfileDto $dto): User;
    public function changePassword(ChangePasswordDto $dto): void;
}