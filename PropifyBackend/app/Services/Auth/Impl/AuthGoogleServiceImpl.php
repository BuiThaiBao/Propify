<?php

namespace App\Services\Auth\Impl;

use App\DTOs\Auth\SocialAuthPayload;
use App\Enums\AuthMethod;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Services\Auth\Adapters\GoogleSocialiteAdapter;
use App\Services\Auth\AuthGoogleService;
use App\Services\Auth\AuthStrategyResolver;
use App\Support\AuthCookieFactory;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Chỉ xử lý OAuth flow (redirect + callback).
 * Logic upsert user được delegate cho UserUpsertService (SRP).
 */
final class AuthGoogleServiceImpl implements AuthGoogleService
{
    public function __construct(
        private readonly AuthStrategyResolver $authStrategyResolver,
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
            if (request()->has('error') || ! request()->has('code')) {
                return redirect(config('app.frontend_url').'/?error=google_auth_failed');
            }

            // Lấy user từ Google OAuth → bọc bằng Adapter
            $googleUser = Socialite::driver('google')->stateless()->user();
            $adapted = new GoogleSocialiteAdapter($googleUser);

            $result = $this->authStrategyResolver
                ->resolve(AuthMethod::GoogleOAuth)
                ->authenticate(new SocialAuthPayload($adapted));

            $response = redirect(config('app.frontend_url').'/login-success');
            foreach (AuthCookieFactory::makeAuthCookies($result, AuthCookieFactory::CLIENT_USER) as $cookie) {
                $response->withCookie($cookie);
            }

            return $response;
        } catch (BusinessException $e) {
            if ($e->getErrorCode() === ErrorCode::AuthAdminNotAllowed) {
                Log::warning('Admin user attempted Google login on client site');

                return redirect(config('app.frontend_url').'/?error=admin_not_allowed');
            }

            Log::error('Google Auth Error', [
                'message' => $e->getMessage(),
            ]);

            return redirect(config('app.frontend_url').'/?error=google_auth_failed');
        } catch (\Exception $e) {
            Log::error('Google Auth Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect(config('app.frontend_url').'/?error=google_auth_failed');
        }
    }
}
