<?php

// ====================================================================
// BƯỚC 8: Routes - Định nghĩa API endpoints
// ====================================================================
//
// GIẢI THÍCH:
//
// Route::prefix('auth') → tất cả routes bên trong có prefix /api/auth/...
// (Laravel tự thêm prefix /api/ cho file routes/api.php)
//
// PUBLIC ROUTES (không cần token):
//   POST /api/auth/register → ai cũng gọi được
//   POST /api/auth/login    → ai cũng gọi được
//
// PROTECTED ROUTES (middleware 'auth:api'):
//   POST /api/auth/logout   → cần token hợp lệ
//   POST /api/auth/refresh  → cần token hợp lệ
//   GET  /api/auth/me       → cần token hợp lệ
//
// middleware('auth:api') hoạt động thế nào?
//   1. Đọc header "Authorization: Bearer eyJhbGciOiJIUzI1NiJ9..."
//   2. JWT Guard giải mã token bằng JWT_SECRET
//   3. Kiểm tra: token hết hạn chưa? có trong blacklist không?
//   4. Lấy claim "sub" (user id) → Users::find(id)
//   5. Nếu OK → cho phép request đi tiếp vào Controller
//   6. Nếu FAIL → throw AuthenticationException → trả về 401
// ====================================================================

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {

    // ===== PUBLIC ROUTES =====
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    // ===== PROTECTED ROUTES (cần JWT token) =====
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me',       [AuthController::class, 'me']);
    });
});
