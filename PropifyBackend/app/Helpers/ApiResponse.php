<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Success response
     */
    public static function success($data = null, string $message = 'Thành công', int $statusCode = 200): JsonResponse
    {
        $response = [
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Created response (201)
     */
    public static function created($data = null, string $message = 'Tạo thành công'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Error response
     */
    public static function error(string $message = 'Có lỗi xảy ra', int $statusCode = 400, $errors = null, ?int $errorCode = null): JsonResponse
    {
        $response = [
            'message' => $message,
        ];

        if (!is_null($errorCode)) {
            $response['error_code'] = $errorCode;
        }

        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Unauthorized response (401)
     */
    public static function unauthorized(string $message = 'Không có quyền truy cập', ?int $errorCode = null): JsonResponse
    {
        return self::error($message, 401, null, $errorCode);
    }

    /**
     * Forbidden response (403)
     */
    public static function forbidden(string $message = 'Bị từ chối truy cập', ?int $errorCode = null): JsonResponse
    {
        return self::error($message, 403, null, $errorCode);
    }

    /**
     * Not found response (404)
     */
    public static function notFound(string $message = 'Không tìm thấy', ?int $errorCode = null): JsonResponse
    {
        return self::error($message, 404, null, $errorCode);
    }

    /**
     * Validation error response (422)
     */
    public static function validationError($errors, string $message = 'Dữ liệu không hợp lệ', ?int $errorCode = null): JsonResponse
    {
        return self::error($message, 422, $errors, $errorCode);
    }

    /**
     * Server error response (500)
     */
    public static function serverError(string $message = 'Lỗi hệ thống', ?int $errorCode = null): JsonResponse
    {
        return self::error($message, 500, null, $errorCode);
    }
}
