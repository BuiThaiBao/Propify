<?php

namespace App\Helpers;

use App\Enums\ErrorCode;
use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Success response (200)
     */
    public static function success(mixed $data = null, string $message = 'Thành công', int $statusCode = 200, ?array $meta = null): JsonResponse
    {
        $response = [
            'status'  => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Created response (201)
     */
    public static function created(mixed $data = null, string $message = 'Tạo thành công'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Error response
     */
    public static function error(
        string $message = 'Có lỗi xảy ra', 
        int $statusCode = 400, 
        mixed $errors = null, 
        ?ErrorCode $errorCode = null
    ): JsonResponse {
        $response = [
            'status'  => false,
            'message' => $message,
        ];

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode->value;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Unauthorized response (401)
     */
    public static function unauthorized(string $message = 'Không có quyền truy cập', ?ErrorCode $errorCode = null): JsonResponse
    {
        return self::error($message, 401, null, $errorCode ?? ErrorCode::AuthUnauthorized);
    }

    /**
     * Forbidden response (403)
     */
    public static function forbidden(string $message = 'Bị từ chối truy cập', ?ErrorCode $errorCode = null): JsonResponse
    {
        return self::error($message, 403, null, $errorCode ?? ErrorCode::AuthForbidden);
    }

    /**
     * Not found response (404)
     */
    public static function notFound(string $message = 'Không tìm thấy', ?ErrorCode $errorCode = null): JsonResponse
    {
        return self::error($message, 404, null, $errorCode ?? ErrorCode::ResourceNotFound);
    }

    /**
     * Validation error response (422)
     */
    public static function validationError(mixed $errors, string $message = 'Dữ liệu không hợp lệ', ?ErrorCode $errorCode = null): JsonResponse
    {
        return self::error($message, 422, $errors, $errorCode ?? ErrorCode::ValidationError);
    }

    /**
     * Server error response (500)
     */
    public static function serverError(string $message = 'Lỗi hệ thống', ?ErrorCode $errorCode = null): JsonResponse
    {
        return self::error($message, 500, null, $errorCode ?? ErrorCode::ServerError);
    }
}
