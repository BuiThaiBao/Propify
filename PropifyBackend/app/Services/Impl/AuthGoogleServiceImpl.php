<?php

namespace App\Services\Impl;
use App\DTOs\Auth\AuthResultDto;
use App\Enums\UserRole;
use App\Models\Users;
use App\Enums\UserStatus;
use App\Repositories\UserRepository;
use App\Services\AuthGoogleService;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthGoogleServiceImpl implements AuthGoogleService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = Users::where('google_id', $googleUser->getId())->first();

        if (!$user) {
            $user = Users::where('email', $googleUser->getEmail())->first();
            if ($user) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                ]);
            } else {
                $user = Users::create([
                    'full_name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => null,
                    'role' => UserRole::User->value,
                    'status' => UserStatus::Active->value,
                ]);
            }
        }
        $token = JWTAuth::fromUser($user);
        return redirect(config("app.frontend_url") . "/login-success?token=$token");
    }
}