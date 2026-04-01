<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\RedirectResponse;

interface AuthGoogleService
{
    public function redirectToGoogle(): RedirectResponse;

    public function handleGoogleCallback(): RedirectResponse;
}