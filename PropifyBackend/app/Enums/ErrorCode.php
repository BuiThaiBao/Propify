<?php

namespace App\Enums;

use Illuminate\Http\Response;

enum ErrorCode: int
{
    // ==================== Auth (1xxx) ====================
    case AuthLoginFailed = 1001;
    case AuthTokenInvalid = 1002;
    case AuthTokenExpired = 1003;
    case AuthUnauthorized = 1004;
    case AuthForbidden = 1005;
    case AuthRegisterFailed = 1006;
    case AuthOtpInvalid = 1007;
    case AuthOtpExpired = 1008;
    case AuthNotVerified = 1009;
    case AuthPhoneNotVerified = 1010;

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

    // ==================== Server (5xxx) ====================
    case ServerError = 5001;
    case ServiceUnavailable = 5002;

    public function message(): string
    {
        return match ($this) {
            self::AuthLoginFailed => 'Email hoac mat khau khong dung',
            self::AuthTokenInvalid => 'Token khong hop le',
            self::AuthTokenExpired => 'Token da het han',
            self::AuthUnauthorized => 'Chua xac thuc',
            self::AuthForbidden => 'Khong co quyen truy cap',
            self::AuthRegisterFailed => 'Dang ky that bai',
            self::AuthOtpInvalid => 'Ma OTP khong hop le',
            self::AuthOtpExpired => 'Ma OTP da het han',
            self::AuthNotVerified => 'Tai khoan chua duoc xac thuc',
            self::AuthPhoneNotVerified => 'Ban can xac thuc so dien thoai truoc khi dang tin',
            self::ValidationError => 'Du lieu khong hop le',
            self::UserNotFound => 'Khong tim thay nguoi dung',
            self::UserAlreadyExists => 'Nguoi dung da ton tai',
            self::UserBanned => 'Tai khoan da bi khoa',
            self::ResourceNotFound => 'Khong tim thay tai nguyen',
            self::ResourceCreateFailed => 'Tao tai nguyen that bai',
            self::ResourceUpdateFailed => 'Cap nhat tai nguyen that bai',
            self::ResourceDeleteFailed => 'Xoa tai nguyen that bai',
            self::ServerError => 'Loi he thong',
            self::ServiceUnavailable => 'Dich vu tam thoi khong kha dung',
        };
    }

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

            self::AuthForbidden,
            self::AuthPhoneNotVerified => Response::HTTP_FORBIDDEN,

            self::ValidationError => Response::HTTP_UNPROCESSABLE_ENTITY,

            self::UserNotFound,
            self::ResourceNotFound => Response::HTTP_NOT_FOUND,

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
