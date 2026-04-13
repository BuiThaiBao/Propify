<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\DTOs\Auth\LoginCredentialsDto;
use App\DTOs\Auth\RegisterUserDto;
use App\Helpers\ApiResponse;
use App\Http\Requests\Auth\CheckResetOtpRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Info(version: "1.0.0", description: "L5-Swagger OpenApi description", title: "Propify API Documentation")]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: "API Server")]
#[OA\SecurityScheme(securityScheme: "bearerAuth", type: "http", scheme: "bearer", bearerFormat: "JWT")]
final class AuthController
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    // Register Documentation
    #[OA\Post(path: "/api/v1/auth/register", operationId: "registerUser", summary: "Register a new user", security: [], tags: ["Authentication"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ["full_name", "phone", "email", "password", "password_confirmation"],
        properties: [
            new OA\Property(property: "full_name", type: "string", example: "Nguyen Van A"),
            new OA\Property(property: "phone", type: "string", example: "0987654321"),
            new OA\Property(property: "email", format: "email", type: "string", example: "user@example.com"),
            new OA\Property(property: "password", format: "password", type: "string", example: "Password1"),
            new OA\Property(property: "password_confirmation", format: "password", type: "string", example: "Password1"),
        ]
    ))]
    #[OA\Response(response: 201, description: "Successful registration")]
    #[OA\Response(response: 422, description: "Validation Error")]
    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = $request->toDto();
        $this->authService->register($dto);

        return ApiResponse::success(
            message: 'Đăng ký thành công. Vui lòng kiểm tra email để nhập mã OTP.',
            statusCode: 202
        );
    }

    // Resend Register OTP
    #[OA\Post(path: "/api/v1/auth/resend-register-otp", operationId: "resendRegisterOtp", summary: "Resend registration OTP", security: [], tags: ["Authentication"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ["email"],
        properties: [
            new OA\Property(property: "email", format: "email", type: "string", example: "user@example.com"),
        ]
    ))]
    #[OA\Response(response: 202, description: "OTP resent successfully")]
    public function resendRegisterOtp(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        
        $this->authService->resendRegisterOtp($request->input('email'));

        return ApiResponse::success(
            message: 'Mã OTP đã được gửi lại tới email của bạn.',
            statusCode: 202
        );
    }

    // Verify OTP
    #[OA\Post(path: "/api/v1/auth/verify-otp", operationId: "verifyOtp", summary: "Verify OTP after registration", security: [], tags: ["Authentication"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ["email", "otp"],
        properties: [
            new OA\Property(property: "email", format: "email", type: "string", example: "user@example.com"),
            new OA\Property(property: "otp", type: "string", example: "123456"),
        ]
    ))]
    #[OA\Response(response: 200, description: "OTP verified, token returned")]
    #[OA\Response(response: 401, description: "Invalid or expired OTP")]
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->authService->verifyOtp(
            email: $request->validated('email'),
            otp: $request->validated('otp'),
        );

        return ApiResponse::success(
            data: new AuthTokenResource($result),
            message: 'Xác thực OTP thành công'
        );
    }

    // Login Documentation
    #[OA\Post(path: "/api/v1/auth/login", operationId: "loginUser", summary: "Login existing user", security: [], tags: ["Authentication"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ["email", "password"],
        properties: [
            new OA\Property(property: "email", format: "email", type: "string", example: "user@example.com"),
            new OA\Property(property: "password", format: "password", type: "string", example: "Password1"),
        ]
    ))]
    #[OA\Response(response: 200, description: "Successful login")]
    #[OA\Response(response: 401, description: "Unauthorized/Invalid Credentials")]
    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginCredentialsDto::fromRequest($request);
        $result = $this->authService->login($dto);

        return ApiResponse::success(
            data: new AuthTokenResource($result),
            message: 'Đăng nhập thành công'
        );
    }

    // Get User Info Documentation
    #[OA\Get(path: "/api/v1/auth/me", operationId: "getAuthenticatedUserInfo", summary: "Get user profile", security: [["bearerAuth" => []]], tags: ["Authentication"])]
    #[OA\Response(response: 200, description: "Successful operation")]
    #[OA\Response(response: 401, description: "Unauthenticated")]
    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        return ApiResponse::success(
            data: new UserResource($user)
        );
    }

    // Logout Documentation
    #[OA\Post(path: "/api/v1/auth/logout", operationId: "logoutUser", summary: "Logout user", security: [["bearerAuth" => []]], tags: ["Authentication"])]
    #[OA\Response(response: 200, description: "Successful logout")]
    #[OA\Response(response: 401, description: "Unauthenticated")]
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return ApiResponse::success(
            message: 'Đăng xuất thành công'
        );
    }

    // Refresh Token Documentation
    #[OA\Post(path: "/api/v1/auth/refresh", operationId: "refreshToken", summary: "Refresh access token", security: [["bearerAuth" => []]], tags: ["Authentication"])]
    #[OA\Response(response: 200, description: "Token successfully refreshed")]
    #[OA\Response(response: 401, description: "Unauthenticated")]
    public function refresh(): JsonResponse
    {
        $token = $this->authService->refresh();

        return ApiResponse::success(
            data: [
                'access_token' => $token,
                'token_type'   => 'bearer',
            ],
            message: 'Token đã được làm mới'
        );
    }

    // Forgot Password
    #[OA\Post(path: "/api/v1/auth/forgot-password", operationId: "forgotPassword", summary: "Send password reset OTP", security: [], tags: ["Authentication"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ["email"],
        properties: [new OA\Property(property: "email", format: "email", type: "string")]
    ))]
    #[OA\Response(response: 200, description: "OTP sent")]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword($request->validated('email'));

        return ApiResponse::success(
            message: 'Mã OTP đã được gửi tới email của bạn.'
        );
    }

    // Check Reset OTP (bước 2 — kiểm tra OTP trước khi cho nhập mật khẩu)
    #[OA\Post(path: "/api/v1/auth/check-reset-otp", operationId: "checkResetOtp", summary: "Check reset OTP without consuming it", security: [], tags: ["Authentication"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ["email", "otp"],
        properties: [
            new OA\Property(property: "email", format: "email", type: "string"),
            new OA\Property(property: "otp", type: "string", example: "123456"),
        ]
    ))]
    #[OA\Response(response: 200, description: "OTP is valid")]
    #[OA\Response(response: 401, description: "Invalid or expired OTP")]
    public function checkResetOtp(CheckResetOtpRequest $request): JsonResponse
    {
        $this->authService->checkResetOtp(
            email: $request->validated('email'),
            otp: $request->validated('otp'),
        );

        return ApiResponse::success(message: 'Mã OTP hợp lệ');
    }

    // Reset Password
    #[OA\Post(path: "/api/v1/auth/reset-password", operationId: "resetPassword", summary: "Reset password with OTP", security: [], tags: ["Authentication"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ["email", "otp", "password", "password_confirmation"],
        properties: [
            new OA\Property(property: "email", format: "email", type: "string"),
            new OA\Property(property: "otp", type: "string", example: "123456"),
            new OA\Property(property: "password", type: "string"),
            new OA\Property(property: "password_confirmation", type: "string"),
        ]
    ))]
    #[OA\Response(response: 200, description: "Password reset successfully")]
    #[OA\Response(response: 401, description: "Invalid OTP")]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->authService->resetPassword(
            email: $data['email'],
            otp: $data['otp'],
            password: $data['password'],
        );

        return ApiResponse::success(
            message: 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập.'
        );
    }
}

