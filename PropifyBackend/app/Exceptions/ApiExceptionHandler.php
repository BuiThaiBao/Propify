<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use App\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

final class ApiExceptionHandler
{
    /**
     * Exception mapping to ErrorCode.
     * Used to reduce duplicated render logic for common framework exceptions.
     *
     * @var array<class-string<Throwable>, ErrorCode>
     */
    protected static array $exceptionMap = [
        AuthenticationException::class     => ErrorCode::AuthUnauthorized,
        AccessDeniedHttpException::class   => ErrorCode::AuthForbidden,
        ModelNotFoundException::class      => ErrorCode::ResourceNotFound,
        NotFoundHttpException::class       => ErrorCode::ResourceNotFound,
        TokenExpiredException::class       => ErrorCode::AuthTokenExpired,
        TokenInvalidException::class       => ErrorCode::AuthTokenInvalid,
        JWTException::class                => ErrorCode::AuthTokenInvalid,
    ];

    /**
     * Register all exception handlers for the API.
     */
    public static function register(Exceptions $exceptions): void
    {
        // 1. Handle our custom Business Exceptions
        $exceptions->render(function (BusinessException $e, Request $request) {
            if (self::isApiRequest($request)) {
                return ApiResponse::error(
                    message: $e->getMessage(),
                    statusCode: $e->getCode(),
                    errorCode: $e->getErrorCode()
                );
            }
        });

        // 2. Handle Validation Exceptions
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (self::isApiRequest($request)) {
                return ApiResponse::validationError($e->errors());
            }
        });

        // 3. Handle Framework Exceptions via Map
        $exceptions->render(function (Throwable $e, Request $request) {
            if (!self::isApiRequest($request)) {
                return null;
            }

            // Check if exception exists in our predefined map
            foreach (self::$exceptionMap as $class => $errorCode) {
                if ($e instanceof $class) {
                    return ApiResponse::error(
                        message: $errorCode->message(),
                        statusCode: $errorCode->httpStatus(),
                        errorCode: $errorCode
                    );
                }
            }

            // Fallback: Generic Server Error — never expose details in production
            return ApiResponse::error(
                message: config('app.debug') ? $e->getMessage() : ErrorCode::ServerError->message(),
                statusCode: ErrorCode::ServerError->httpStatus(),
                errors: config('app.debug') ? ['exception' => get_class($e), 'trace' => $e->getTraceAsString()] : null,
                errorCode: ErrorCode::ServerError
            );
        });
    }

    /**
     * Determine if the request wants an API JSON response.
     */
    private static function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }
}
