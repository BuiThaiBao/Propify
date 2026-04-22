<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\DTOs\Auth\LoginCredentialsDto;
use App\Helpers\ApiResponse;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\Requests\Auth\Auth\CheckResetOtpRequest;
use App\Http\Resources\Requests\Auth\Auth\ForgotPasswordRequest;
use App\Http\Resources\Requests\Auth\Auth\LoginRequest;
use App\Http\Resources\Requests\Auth\Auth\RegisterRequest;
use App\Http\Resources\Requests\Auth\Auth\ResetPasswordRequest;
use App\Http\Resources\Requests\Auth\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


final class AuthController
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    // Register Documentation






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






    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        return ApiResponse::success(
            data: new UserResource($user)
        );
    }

    // Logout Documentation





    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return ApiResponse::success(
            message: 'Đăng xuất thành công'
        );
    }



    // Refresh Token Documentation





    public function refresh(): JsonResponse
    {
        $token = $this->authService->refresh();

        return ApiResponse::success(
            data: [
                'access_token' => $token,
                'token_type' => 'bearer',
            ],
            message: 'Token đã được làm mới'
        );
    }



    // Forgot Password

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword($request->validated('email'));

        return ApiResponse::success(
            message: 'Mã OTP đã được gửi tới email của bạn.'
        );
    }





    // Check Reset OTP (bước 2 — kiểm tra OTP trước khi cho nhập mật khẩu)






    public function checkResetOtp(CheckResetOtpRequest $request): JsonResponse
    {
        $this->authService->checkResetOtp(
            email: $request->validated('email'),
            otp: $request->validated('otp'),
        );

        return ApiResponse::success(message: 'Mã OTP hợp lệ');
    }





    // Reset Password




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

