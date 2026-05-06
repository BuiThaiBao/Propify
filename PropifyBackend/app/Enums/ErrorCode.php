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
    case AuthOtpInvalid     = 1007;  // OTP sai hoặc đã dùng
    case AuthOtpExpired     = 1008;  // OTP hết hạn (Redis TTL)
    case AuthNotVerified    = 1009;  // Tài khoản chưa xác thực OTP
    case AuthPasswordIncorrect = 1010;

    case AuthPhoneNotVerified = 1011;


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
    case SlotNotOwner             = 6007;
    case SlotTimeOverlap          = 6008;
    case SlotHasApprovedBooking   = 6009;
    case SlotListingMismatch      = 6010;
    case BookingExistsOnListing   = 6011;
    case ListingCannotBeLocked    = 6012;
    case ListingAlreadyLocked     = 6013;
    case BookingNotPending        = 6012;
    case BookingNotFound          = 6013;
    case BookingNotOwner          = 6014;
    case BookingTooLateToCancel   = 6015;
    case ListingNotActive         = 6016;

    // ==================== Chat (7xxx) ====================
    case ConversationNotFound              = 7001;
    case UnauthorizedConversationAccess    = 7002;

    // ==================== Server (5xxx) ====================
    case ServerError = 5001;
    case ServiceUnavailable = 5002;

    // Package (8xxx)
    case PackageAlreadyExists = 8001;
    case PackageNotFound = 8002;
    case PackageInactive = 8003;
    case ListingUpgradeNotAllowed = 8004;
    case ListingNotOwned = 8005;




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
            self::AuthPasswordIncorrect => 'Mật khẩu hiện tại không đúng',
            self::ValidationError => 'Dữ liệu không hợp lệ',
            self::AuthPhoneNotVerified => 'Bạn cần cập nhật số điện thoại trước khi đăng tin',
            self::UserNotFound => 'Không tìm thấy người dùng',
            self::UserAlreadyExists => 'Người dùng đã tồn tại',
            self::UserBanned => 'Tài khoản đã bị khóa',
            self::ResourceNotFound => 'Không tìm thấy tài nguyên',
            self::ResourceCreateFailed => 'Tạo tài nguyên thất bại',
            self::ResourceUpdateFailed => 'Cập nhật tài nguyên thất bại',
            self::ResourceDeleteFailed => 'Xóa tài nguyên thất bại',
            self::ListingNotFound => 'Không tìm thấy bài đăng',
            self::AppointmentSlotNotFound => 'Bạn không có cấu hình lịch hẹn nào cho bài đăng này hoặc bạn không có quyền truy cập',
            self::ConversationNotFound => 'Cuộc trò chuyện không tồn tại',
            self::UnauthorizedConversationAccess => 'Bạn không có quyền truy cập vào cuộc trò chuyện này',
            self::BookingSelfSlot => 'Bạn không thể đặt lịch hẹn cho chính bài đăng của mình',
            self::BookingInvalidDate => 'Ngày hoặc giờ hẹn không hợp lệ',
            self::BookingSlotNotFound => 'Khung giờ hẹn không tồn tại hoặc đã bị vô hiệu hóa',
            self::BookingDuplicate => 'Bạn đã đặt lịch hẹn cho khung giờ này vào ngày này rồi',
            self::SlotNotOwner => 'Bạn không có quyền chỉnh sửa khung giờ này',
            self::SlotTimeOverlap => 'Khung giờ này bị trùng với khung giờ đã có trong cùng ngày',
            self::SlotHasApprovedBooking => 'Không thể sửa vì đã có lịch hẹn được duyệt trong khung giờ này',
            self::SlotListingMismatch => 'Khung giờ không thuộc bài đăng này',
            self::BookingExistsOnListing => 'Bạn đã có một lịch hẹn chưa hoàn thành trên căn hộ này',
            self::ListingCannotBeLocked => 'Không thể khóa tin này do trạng thái hiện tại không hợp lệ',
            self::ListingAlreadyLocked => 'Tin đăng đã được khóa trước đó',
            self::BookingNotPending => 'Lịch hẹn không ở trạng thái chờ xác nhận',
            self::BookingNotFound => 'Không tìm thấy lịch hẹn',
            self::BookingNotOwner => 'Bạn không phải là chủ lịch hẹn',
            self::BookingTooLateToCancel => 'Chỉ có thể hủy lịch trước giờ hẹn ít nhất 2 tiếng',
            self::ListingNotActive => 'Bài đăng hiện không khả dụng để đặt lịch',
            self::ServerError => 'Lỗi hệ thống',
            self::ServiceUnavailable => 'Dịch vụ tạm thời không khả dụng',
            self::PackageAlreadyExists => "Gói tin đã tồn tại",
            self::PackageNotFound => "Không tìm thấy gói tin",
            self::PackageInactive => "Gói tin đã bị vô hiệu hóa",
            self::ListingUpgradeNotAllowed => "Không thể nâng cấp gói tin. Chỉ cho phép nâng cấp lên gói cao hơn.",
            self::ListingNotOwned => "Bạn không phải chủ sở hữu tin đăng này",
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

            self::AuthPasswordIncorrect,
            self::AuthPhoneNotVerified => Response::HTTP_UNPROCESSABLE_ENTITY,

            self::AuthForbidden => Response::HTTP_FORBIDDEN,

            self::ValidationError => Response::HTTP_UNPROCESSABLE_ENTITY,

            self::UserNotFound,
            self::ResourceNotFound,
            self::ListingNotFound,
            self::AppointmentSlotNotFound,
            self::ConversationNotFound => Response::HTTP_NOT_FOUND,

            self::UnauthorizedConversationAccess => Response::HTTP_FORBIDDEN,
            self::AppointmentSlotNotFound => Response::HTTP_NOT_FOUND,

            self::BookingSelfSlot => Response::HTTP_FORBIDDEN,

            self::BookingInvalidDate => Response::HTTP_UNPROCESSABLE_ENTITY,

            self::BookingSlotNotFound => Response::HTTP_NOT_FOUND,

            self::BookingDuplicate => Response::HTTP_CONFLICT,

            self::SlotNotOwner => Response::HTTP_FORBIDDEN,

            self::SlotTimeOverlap => Response::HTTP_CONFLICT,

            self::SlotHasApprovedBooking => Response::HTTP_CONFLICT,

            self::SlotListingMismatch => Response::HTTP_UNPROCESSABLE_ENTITY,

            self::BookingExistsOnListing => Response::HTTP_CONFLICT,

            self::ListingCannotBeLocked => Response::HTTP_UNPROCESSABLE_ENTITY,

            self::ListingAlreadyLocked => Response::HTTP_CONFLICT,

            self::BookingNotPending => Response::HTTP_CONFLICT,

            self::BookingNotFound => Response::HTTP_NOT_FOUND,

            self::BookingNotOwner => Response::HTTP_FORBIDDEN,

            self::BookingTooLateToCancel => Response::HTTP_CONFLICT,

            self::ListingNotActive => Response::HTTP_GONE,

            self::UserAlreadyExists => Response::HTTP_CONFLICT,
            self::PackageAlreadyExists => Response::HTTP_CONFLICT,

            self::PackageNotFound => Response::HTTP_NOT_FOUND,

            self::PackageInactive => Response::HTTP_UNPROCESSABLE_ENTITY,
            self::ListingUpgradeNotAllowed => Response::HTTP_UNPROCESSABLE_ENTITY,
            self::ListingNotOwned => Response::HTTP_FORBIDDEN,

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
