<?php

namespace App\Enums;

use Illuminate\Http\Response;

enum ErrorCode: int
{
    // ==================== Auth (1xxx) ====================
    case AuthLoginFailed    = 1001;
    case AuthTokenInvalid   = 1002;
    case AuthTokenExpired   = 1003;
    case AuthUnauthorized   = 1004;
    case AuthForbidden      = 1005;
    case AuthRegisterFailed = 1006;
    case AuthOtpInvalid     = 1007;  // OTP sai hoặc đã dùng
    case AuthOtpExpired     = 1008;  // OTP hết hạn (Redis TTL)
    case AuthNotVerified    = 1009;  // Tài khoản chưa xác thực OTP

    // ==================== Validation (2xxx) ====================
    case ValidationError = 2001;

    // ==================== User (3xxx) ====================
    case UserNotFound = 3001;
    case UserAlreadyExists = 3002;
    case UserBanned = 3003;

    // ==================== Resource (4xxx) ====================
    case ResourceNotFound = 4001;
    case ResourceCreateFailed = 4002;
    case ResourceUpdateFailed = 4003;
    case ResourceDeleteFailed = 4004;

    // ==================== Appointment (6xxx) ====================
    case ListingNotFound          = 6001;
    case AppointmentSlotNotFound  = 6002;
    case BookingSelfSlot          = 6003;
    case BookingInvalidDate       = 6004;
    case BookingSlotNotFound      = 6005;
    case BookingDuplicate         = 6006;

    // ==================== Server (5xxx) ====================
    case ServerError = 5001;
    case ServiceUnavailable = 5002;

    /**
     * Get the descriptive message for the error code.
     */
    public function message(): string
    {
        return match ($this) {
            self::AuthLoginFailed    => 'Email hoặc mật khẩu không đúng',
            self::AuthTokenInvalid   => 'Token không hợp lệ',
            self::AuthTokenExpired   => 'Token đã hết hạn',
            self::AuthUnauthorized   => 'Chưa xác thực',
            self::AuthForbidden      => 'Không có quyền truy cập',
            self::AuthRegisterFailed => 'Đăng ký thất bại',
            self::AuthOtpInvalid     => 'Mã OTP không hợp lệ',
            self::AuthOtpExpired     => 'Mã OTP đã hết hạn',
            self::AuthNotVerified    => 'Tài khoản chưa được xác thực',
            self::ValidationError => 'Dữ liệu không hợp lệ',
            self::UserNotFound => 'Không tìm thấy người dùng',
            self::UserAlreadyExists => 'Người dùng đã tồn tại',
            self::UserBanned => 'Tài khoản đã bị khóa',
            self::ResourceNotFound => 'Không tìm thấy tài nguyên',
            self::ResourceCreateFailed => 'Tạo tài nguyên thất bại',
            self::ResourceUpdateFailed => 'Cập nhật tài nguyên thất bại',
            self::ResourceDeleteFailed => 'Xóa tài nguyên thất bại',
            self::ListingNotFound => 'Không tìm thấy bài đăng',
            self::AppointmentSlotNotFound => 'Bạn không có cấu hình lịch hẹn nào cho bài đăng này hoặc bạn không có quyền truy cập',
            self::BookingSelfSlot => 'Bạn không thể đặt lịch hẹn cho chính bài đăng của mình',
            self::BookingInvalidDate => 'Ngày hoặc giờ hẹn không hợp lệ',
            self::BookingSlotNotFound => 'Khung giờ hẹn không tồn tại hoặc đã bị vô hiệu hóa',
            self::BookingDuplicate => 'Bạn đã đặt lịch hẹn cho khung giờ này vào ngày này rồi',
            self::ServerError => 'Lỗi hệ thống',
            self::ServiceUnavailable => 'Dịch vụ tạm thời không khả dụng',
        };
    }

    /**
     * Get the appropriate HTTP status code for this business error.
     */
    public function httpStatus(): int
    {
        return match ($this) {
            self::AuthLoginFailed,
            self::AuthTokenInvalid,
            self::AuthTokenExpired,
            self::AuthUnauthorized,
            self::AuthOtpInvalid,
            self::AuthOtpExpired,
            self::AuthNotVerified => Response::HTTP_UNAUTHORIZED,

            self::AuthForbidden => Response::HTTP_FORBIDDEN,

            self::ValidationError => Response::HTTP_UNPROCESSABLE_ENTITY,

            self::UserNotFound,
            self::ResourceNotFound,
            self::ListingNotFound,
            self::AppointmentSlotNotFound => Response::HTTP_NOT_FOUND,

            self::BookingSelfSlot => Response::HTTP_FORBIDDEN,

            self::BookingInvalidDate => Response::HTTP_UNPROCESSABLE_ENTITY,

            self::BookingSlotNotFound => Response::HTTP_NOT_FOUND,

            self::BookingDuplicate => Response::HTTP_CONFLICT,

            self::UserAlreadyExists => Response::HTTP_CONFLICT,

            self::ServerError,
            self::AuthRegisterFailed,
            self::ResourceCreateFailed,
            self::ResourceUpdateFailed,
            self::ResourceDeleteFailed => Response::HTTP_INTERNAL_SERVER_ERROR,

            self::ServiceUnavailable => Response::HTTP_SERVICE_UNAVAILABLE,

            default => Response::HTTP_BAD_REQUEST,
        };
    }
}
