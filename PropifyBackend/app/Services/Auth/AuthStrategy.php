<?php

namespace App\Services\Auth;

use App\DTOs\Auth\AuthPayload;
use App\DTOs\Auth\AuthResultDto;
use App\Enums\AuthMethod;

interface AuthStrategy
{
    public function method(): AuthMethod;

    public function authenticate(AuthPayload $payload): AuthResultDto;
}
