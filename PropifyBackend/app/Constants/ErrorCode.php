<?php

namespace App\Constants;

class ErrorCode
{
    // ==================== Auth (1xxx) ====================
    public const AUTH_LOGIN_FAILED       = 1001;
    public const AUTH_TOKEN_INVALID      = 1002;
    public const AUTH_TOKEN_EXPIRED      = 1003;
    public const AUTH_UNAUTHORIZED       = 1004;
    public const AUTH_FORBIDDEN          = 1005;
    public const AUTH_REGISTER_FAILED    = 1006;

    // ==================== Validation (2xxx) ====================
    public const VALIDATION_ERROR        = 2001;

    // ==================== User (3xxx) ====================
    public const USER_NOT_FOUND          = 3001;
    public const USER_ALREADY_EXISTS     = 3002;
    public const USER_BANNED             = 3003;

    // ==================== Resource (4xxx) ====================
    public const RESOURCE_NOT_FOUND      = 4001;
    public const RESOURCE_CREATE_FAILED  = 4002;
    public const RESOURCE_UPDATE_FAILED  = 4003;
    public const RESOURCE_DELETE_FAILED  = 4004;

    // ==================== Server (5xxx) ====================
    public const SERVER_ERROR            = 5001;
    public const SERVICE_UNAVAILABLE     = 5002;

    /**
     * Mô tả mặc định cho từng mã lỗi
     */
    public static function message(int $code): string
    {
        return match ($code) {
            self::AUTH_LOGIN_FAILED      => 'Email hoặc mật khẩu không đúng',
            self::AUTH_TOKEN_INVALID     => 'Token không hợp lệ',
            self::AUTH_TOKEN_EXPIRED     => 'Token đã hết hạn',
            self::AUTH_UNAUTHORIZED      => 'Chưa xác thực',
            self::AUTH_FORBIDDEN         => 'Không có quyền truy cập',
            self::AUTH_REGISTER_FAILED   => 'Đăng ký thất bại',
            self::VALIDATION_ERROR       => 'Dữ liệu không hợp lệ',
            self::USER_NOT_FOUND         => 'Không tìm thấy người dùng',
            self::USER_ALREADY_EXISTS    => 'Người dùng đã tồn tại',
            self::USER_BANNED            => 'Tài khoản đã bị khóa',
            self::RESOURCE_NOT_FOUND     => 'Không tìm thấy tài nguyên',
            self::RESOURCE_CREATE_FAILED => 'Tạo tài nguyên thất bại',
            self::RESOURCE_UPDATE_FAILED => 'Cập nhật tài nguyên thất bại',
            self::RESOURCE_DELETE_FAILED => 'Xóa tài nguyên thất bại',
            self::SERVER_ERROR           => 'Lỗi hệ thống',
            self::SERVICE_UNAVAILABLE    => 'Dịch vụ tạm thời không khả dụng',
            default                      => 'Có lỗi xảy ra',
        };
    }
}
