<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Services\AuthGoogleService;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class GoogleController
{
    public function __construct(
        private readonly AuthGoogleService $authGoogleService
    ) {
    }

    public function redirectToGoogle(): RedirectResponse
    {
        return $this->authGoogleService->redirectToGoogle();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        return $this->authGoogleService->handleGoogleCallback();
    }
}
