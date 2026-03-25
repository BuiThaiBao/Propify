<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\DTOs\Auth\LoginCredentialsDto;
use App\DTOs\Auth\RegisterUserDto;
use App\Helpers\ApiResponse;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
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
            new OA\Property(property: "password", format: "password", type: "string", example: "password123"),
            new OA\Property(property: "password_confirmation", format: "password", type: "string", example: "password123"),
        ]
    ))]
    #[OA\Response(response: 201, description: "Successful registration")]
    #[OA\Response(response: 422, description: "Validation Error")]

    // Register Logic
    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterUserDto::fromRequest($request);
        $result = $this->authService->register($dto);

        return ApiResponse::created(
            data: new AuthTokenResource($result),
            message: 'Đăng ký thành công'
        );
    }







    // Login Documentation
    #[OA\Post(path: "/api/v1/auth/login", operationId: "loginUser", summary: "Login existing user", security: [], tags: ["Authentication"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ["email", "password"],
        properties: [
            new OA\Property(property: "email", format: "email", type: "string", example: "user@example.com"),
            new OA\Property(property: "password", format: "password", type: "string", example: "password123"),
        ]
    ))]
    #[OA\Response(response: 200, description: "Successful login")]
    #[OA\Response(response: 401, description: "Unauthorized/Invalid Credentials")]

    // Login Logic
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

    // Get User Info Logic
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

    // Logout Logic
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

    // Refresh Token Logic
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
}
