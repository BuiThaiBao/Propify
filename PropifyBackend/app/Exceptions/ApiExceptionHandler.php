<?php

namespace App\Exceptions;

// ====================================================================
// BƯỚC 9a: Exception Handler - Xử lý lỗi JWT thống nhất
// ====================================================================
//
// GIẢI THÍCH:
// Khi JWT token có vấn đề, tymon/jwt-auth sẽ throw exception:
//
// 1. TokenExpiredException: Token đã hết hạn (quá TTL phút)
//    → Client cần gọi /refresh hoặc /login lại
//
// 2. TokenInvalidException: Token bị sai format hoặc bị sửa đổi
//    → Ai đó cố giả mạo token
//
// 3. JWTException: Lỗi JWT chung (không có token, lỗi decode...)
//    → Catch-all cho các lỗi JWT khác
//
// Nếu KHÔNG có handler này:
//    → Laravel trả về HTML error page (không phải JSON)
//    → Client API không parse được response
//
// Handler này đảm bảo TẤT CẢ lỗi trả về JSON format chuẩn:
//    { status: false, message: "...", error_code: 1002 }
// ====================================================================

use App\Constants\ErrorCode;
use App\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Throwable;

class ApiExceptionHandler
{
    /**
     * Đăng ký tất cả exception handler cho API.
     * Gọi method này trong bootstrap/app.php -> withExceptions()
     */
    public static function register(\Illuminate\Foundation\Configuration\Exceptions $exceptions): void
    {
        // --- Validation errors (422) ---
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    ErrorCode::message(ErrorCode::VALIDATION_ERROR),
                    422,
                    $e->errors(),
                    ErrorCode::VALIDATION_ERROR
                );
            }
        });

        // --- Authentication errors (401) ---
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    ErrorCode::message(ErrorCode::AUTH_UNAUTHORIZED),
                    401,
                    null,
                    ErrorCode::AUTH_UNAUTHORIZED
                );
            }
        });

        // --- JWT: Token hết hạn (401) ---
        $exceptions->render(function (TokenExpiredException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    ErrorCode::message(ErrorCode::AUTH_TOKEN_EXPIRED),
                    401,
                    null,
                    ErrorCode::AUTH_TOKEN_EXPIRED
                );
            }
        });

        // --- JWT: Token không hợp lệ (401) ---
        $exceptions->render(function (TokenInvalidException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    ErrorCode::message(ErrorCode::AUTH_TOKEN_INVALID),
                    401,
                    null,
                    ErrorCode::AUTH_TOKEN_INVALID
                );
            }
        });

        // --- JWT: Lỗi JWT chung (401) ---
        $exceptions->render(function (JWTException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    ErrorCode::message(ErrorCode::AUTH_TOKEN_INVALID),
                    401,
                    null,
                    ErrorCode::AUTH_TOKEN_INVALID
                );
            }
        });

        // --- Access denied (403) ---
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    ErrorCode::message(ErrorCode::AUTH_FORBIDDEN),
                    403,
                    null,
                    ErrorCode::AUTH_FORBIDDEN
                );
            }
        });

        // --- Model not found (404) ---
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    ErrorCode::message(ErrorCode::RESOURCE_NOT_FOUND),
                    404,
                    null,
                    ErrorCode::RESOURCE_NOT_FOUND
                );
            }
        });

        // --- Route not found (404) ---
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    ErrorCode::message(ErrorCode::RESOURCE_NOT_FOUND),
                    404,
                    null,
                    ErrorCode::RESOURCE_NOT_FOUND
                );
            }
        });

        // --- Fallback: tất cả lỗi khác (500) ---
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    config('app.debug') ? $e->getMessage() : ErrorCode::message(ErrorCode::SERVER_ERROR),
                    500,
                    config('app.debug') ? ['exception' => get_class($e)] : null,
                    ErrorCode::SERVER_ERROR
                );
            }
        });
    }
}
