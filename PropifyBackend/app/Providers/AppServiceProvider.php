<?php

namespace App\Providers;

// ====================================================================
// BƯỚC 6: Đăng ký Service trong IoC Container
// ====================================================================
//
// GIẢI THÍCH:
// Laravel IoC (Inversion of Control) Container:
//   - Khi Controller khai báo: __construct(AuthService $authService)
//   - Laravel TỰ ĐỘNG tìm cách tạo AuthService
//   - Nhưng AuthService là INTERFACE → không thể new trực tiếp!
//   - Cần nói cho Laravel biết: "Khi cần AuthService, hãy dùng AuthServiceImpl"
//
// $this->app->bind(Interface, Implementation):
//   - Mỗi lần cần → tạo instance mới
//   - Dùng $this->app->singleton() nếu muốn dùng chung 1 instance
//
// LUỒNG: Request → Controller cần AuthService → Container tìm binding
//         → Thấy bind(AuthService, AuthServiceImpl) → new AuthServiceImpl()
//         → Inject vào Controller
// ====================================================================

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
        // Đăng ký: Khi inject AuthService → dùng AuthServiceImpl
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
