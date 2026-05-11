<?php

namespace App\Services\Impl;

use App\Services\Auth\Adapters\GoogleSocialiteAdapter;
use App\Services\Auth\UserUpsertService;
use App\Services\AuthGoogleService;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Chỉ xử lý OAuth flow (redirect + callback).
 * Logic upsert user được delegate cho UserUpsertService (SRP).
 */
final class AuthGoogleServiceImpl implements AuthGoogleService
{
    public function __construct(
        private readonly UserUpsertService $userUpsertService,
    ) {}

    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            if (request()->has('error') || !request()->has('code')) {
                return redirect(config('app.frontend_url') . "/?error=google_auth_failed");
            }

            // Lấy user từ Google OAuth → bọc bằng Adapter
            $googleUser = Socialite::driver('google')->stateless()->user();
            $adapted = new GoogleSocialiteAdapter($googleUser);

            // Delegate logic tìm/tạo/liên kết user cho UserUpsertService
            $user = $this->userUpsertService->upsertFromSocial($adapted);

            if ($user->role === \App\Enums\UserRole::Admin) {
                Log::warning('Admin user attempted Google login on client site', ['user_id' => $user->id]);
                return redirect(config('app.frontend_url') . "/?error=admin_not_allowed");
            }

            $token = JWTAuth::fromUser($user);

            return redirect(config('app.frontend_url') . "/login-success?token=$token");
        } catch (\Exception $e) {
            Log::error('Google Auth Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect(config('app.frontend_url') . "/?error=google_auth_failed");
        }
    }
}