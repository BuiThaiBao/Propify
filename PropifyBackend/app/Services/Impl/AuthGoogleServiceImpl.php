<?php

namespace App\Services\Impl;

use App\Enums\UserRole;
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

        // 1) Tìm user theo google_id
        $user = $this->userRepository->findByGoogleId($googleUser->getId());

        if (!$user) {
            // 2) Tìm theo email (user đã register bằng email trước đó)
            $user = $this->userRepository->findByEmail($googleUser->getEmail());

            if ($user) {
                // Liên kết google_id vào tài khoản hiện có
                $user->update([
                    'google_id' => $googleUser->getId(),
                ]);
            } else {
                // 3) Tạo user mới
                $user = $this->userRepository->create([
                    'full_name' => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password'  => null,
                    'role'      => UserRole::User->value,
                    'status'    => UserStatus::Active->value,
                ]);
            }
        }

        $token = JWTAuth::fromUser($user);

        return redirect(config('app.frontend_url') . "/login-success?token=$token");
    }
}