<?php

namespace App\Services\Auth;

use Symfony\Component\HttpFoundation\RedirectResponse;

interface AuthGoogleService
{
    public function redirectToGoogle(): RedirectResponse;

    public function handleGoogleCallback(): RedirectResponse;
}