<?php

// ====================================================================
// BƯỚC 9b: Bootstrap - Kết nối Exception Handler
// ====================================================================
//
// GIẢI THÍCH:
// File này là "điểm khởi đầu" của ứng dụng Laravel.
// - withRouting(): đăng ký route files
// - withMiddleware(): cấu hình middleware
// - withExceptions(): đăng ký exception handlers
//
// ApiExceptionHandler::register($exceptions):
//   → Đăng ký tất cả handler đã định nghĩa ở bước 9a
//   → Đảm bảo mọi lỗi API đều trả JSON format chuẩn
// ====================================================================

use App\Exceptions\ApiExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        ApiExceptionHandler::register($exceptions);
    })->create();
