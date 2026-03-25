<?php

namespace App\Providers;

use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\Impl\AuthServiceImpl;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Domain Interfaces to Implementations
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(AuthService::class, AuthServiceImpl::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
