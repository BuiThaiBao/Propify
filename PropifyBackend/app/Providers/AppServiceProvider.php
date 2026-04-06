<?php

namespace App\Providers;

use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\UserRepository;
use App\Services\AuthGoogleService;
use App\Services\AuthService;
use App\Services\Impl\AuthGoogleServiceImpl;
use App\Services\Impl\AuthServiceImpl;
use App\Services\Impl\TokenProcessServiceImpl;
use App\Services\TokenProcessService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Domain Interfaces to Implementations
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(AuthService::class, AuthServiceImpl::class);
        $this->app->bind(AuthGoogleService::class, AuthGoogleServiceImpl::class);
        $this->app->bind(TokenProcessService::class, TokenProcessServiceImpl::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
