<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Services\AuthGoogleService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleController
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
