<?php

namespace App\Http\Controllers;

// ====================================================================
// BƯỚC 7: AuthController - Xử lý HTTP Request/Response
// ====================================================================
//
// GIẢI THÍCH:
// Controller có nhiệm vụ DUY NHẤT:
//   1. Nhận HTTP request (đã qua validation bởi FormRequest)
//   2. Gọi Service xử lý business logic
//   3. Trả HTTP response (dùng ApiResponse helper)
//
// Controller KHÔNG chứa business logic (không hash password, không tạo token...)
// → Tất cả logic nằm trong AuthServiceImpl
//
// LUỒNG: Client → Route → (Middleware) → Controller → Service → Response
//
// Dependency Injection:
//   __construct(AuthService $authService)
//   → Laravel IoC Container tự inject AuthServiceImpl (đã bind ở AppServiceProvider)
// ====================================================================

use App\Constants\ErrorCode;
use App\Helpers\ApiResponse;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        // Laravel tự inject AuthServiceImpl vào đây
        $this->authService = $authService;
    }

    /**
     * Đăng ký tài khoản mới
     *
     * POST /api/auth/register
     * Body: { full_name, phone, email, password, password_confirmation }
     * Response: 201 { status, message, data: { user, token } }
     */
    public function register(RegisterRequest $request)
    {
        // $request->validated() chỉ trả về data đã pass validation
        $result = $this->authService->register($request->validated());

        return ApiResponse::created($result, 'Đăng ký thành công');
    }

    /**
     * Đăng nhập
     *
     * POST /api/auth/login
     * Body: { email, password }
     * Response: 200 { status, message, data: { token } }
     */
    public function login(LoginRequest $request)
    {
        $token = $this->authService->login($request->validated());

        if (!$token) {
            return ApiResponse::unauthorized(
                'Email hoặc mật khẩu không đúng',
                ErrorCode::AUTH_LOGIN_FAILED
            );
        }

        return ApiResponse::success(['token' => $token], 'Đăng nhập thành công');
    }

    /**
     * Đăng xuất (cần token)
     *
     * POST /api/auth/logout
     * Header: Authorization: Bearer <token>
     * Response: 200 { status, message }
     */
    public function logout()
    {
        $this->authService->logout();

        return ApiResponse::success(null, 'Đăng xuất thành công');
    }

    /**
     * Refresh token (cần token cũ)
     *
     * POST /api/auth/refresh
     * Header: Authorization: Bearer <old_token>
     * Response: 200 { status, message, data: { token: new_token } }
     */
    public function refresh()
    {
        $token = $this->authService->refresh();

        return ApiResponse::success(['token' => $token], 'Token đã được làm mới');
    }

    /**
     * Lấy thông tin user hiện tại (cần token)
     *
     * GET /api/auth/me
     * Header: Authorization: Bearer <token>
     * Response: 200 { status, message, data: { id, full_name, email, ... } }
     */
    public function me()
    {
        $user = $this->authService->me();

        return ApiResponse::success($user);
    }
}
