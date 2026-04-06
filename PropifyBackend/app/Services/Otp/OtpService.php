<?php

namespace App\Services\Otp;

use App\Models\User;

interface OtpService
{
    /**
     * Tạo OTP ngẫu nhiên, lưu Redis với TTL 3 phút, gửi qua notification.
     */
    public function generate(User $user): string;

    /**
     * Xác thực OTP. Trả về true nếu hợp lệ và xóa khỏi Redis.
     */
    public function verify(User $user, string $otp): bool;

    /**
     * Xóa OTP khỏi Redis (dùng sau khi verify thành công).
     */
    public function invalidate(User $user): void;
}
