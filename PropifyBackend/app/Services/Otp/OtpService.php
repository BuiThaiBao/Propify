<?php

namespace App\Services\Otp;

use App\Enums\OtpContext;
use App\Models\User;

interface OtpService
{
    /**
     * Sinh OTP, lưu Redis TTL 3 phút, gửi mail.
     */
    public function generate(User $user, OtpContext $context): string;

    /**
     * Xác thực OTP. Đúng → xóa Redis, trả true.
     */
    public function verify(User $user, string $otp, OtpContext $context): bool;

    /**
     * Kiểm tra OTP hợp lệ mà KHÔNG xóa Redis.
     * Dùng cho step 2 quên mật khẩu — step 3 vẫn cần OTP để reset.
     */
    public function peek(User $user, string $otp, OtpContext $context): bool;

    /**
     * Xóa OTP khỏi Redis.
     */
    public function invalidate(User $user, OtpContext $context): void;
}
