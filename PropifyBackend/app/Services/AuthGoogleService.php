<?php

namespace App\Services;

use App\DTOs\Auth\AuthResultDto;
use Symfony\Component\HttpFoundation\RedirectResponse;

interface AuthGoogleService
{
    public function redirectToGoogle(): RedirectResponse;

    public function handleGoogleCallback(): RedirectResponse;
}