<?php

namespace App\Providers;

use App\Events\Auth\UserRegistered;
use App\Listeners\Auth\SendWelcomeNotification;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\UserRepository;
use App\Services\AuthGoogleService;
use App\Services\AuthService;
use App\Services\Impl\AuthGoogleServiceImpl;
use App\Services\Impl\AuthServiceImpl;
use App\Services\Impl\TokenProcessServiceImpl;
use App\Services\Notification\Channel\EmailChannel;
use App\Services\Notification\Impl\NotificationServiceImpl;
use App\Services\Notification\NotificationService;
use App\Services\Otp\Impl\OtpServiceImpl;
use App\Services\Otp\OtpService;
use App\Services\TokenProcessService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ── Repository bindings ───────────────────────────────────────────
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);

        // ── Auth bindings ─────────────────────────────────────────────────
        $this->app->bind(AuthService::class, AuthServiceImpl::class);
        $this->app->bind(AuthGoogleService::class, AuthGoogleServiceImpl::class);
        $this->app->bind(TokenProcessService::class, TokenProcessServiceImpl::class);

        // ── Notification bindings ─────────────────────────────────────────
        // NotificationServiceImpl nhận array $channels qua constructor
        // Thêm channel mới chỉ cần append vào array này
        $this->app->bind(NotificationService::class, function () {
            return new NotificationServiceImpl(channels: [
                app(EmailChannel::class),
                // app(SmsChannel::class),   // bật khi có
                // app(ZaloChannel::class),  // bật khi có
            ]);
        });

        // ── OTP binding ───────────────────────────────────────────────────
        $this->app->bind(OtpService::class, OtpServiceImpl::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Event → Listener mapping (Laravel 11 style) ───────────────────
        Event::listen(UserRegistered::class, SendWelcomeNotification::class);
    }
}
