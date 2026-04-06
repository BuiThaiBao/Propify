<?php

namespace App\Services\Otp\Impl;

use App\Enums\MailType;
use App\Enums\NotificationChanelType;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\Redis;

final class OtpServiceImpl implements OtpService
{
    /** TTL của OTP tính bằng giây — 3 phút */
    private const OTP_TTL_SECONDS = 180;

    /** Độ dài OTP */
    private const OTP_LENGTH = 6;

    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Sinh OTP, lưu Redis 3 phút, gửi mail cho user.
     */
    public function generate(User $user): string
    {
        $otp = $this->generateOtp();

        // ⚠️ Không dùng named args — Redis facade dùng __call() nên phải positional
        Redis::setex($this->redisKey($user), self::OTP_TTL_SECONDS, $otp);

        // Gửi mail chứa OTP
        $this->notificationService->send(
            user: $user,
            template: MailType::VERIFY_EMAIL,
            data: ['otp' => $otp],
            channels: [NotificationChanelType::EMAIL],
        );

        return $otp;
    }

    /**
     * Xác thực OTP. Đúng → xóa Redis, trả true.
     */
    public function verify(User $user, string $otp): bool
    {
        $stored = Redis::get($this->redisKey($user));

        if (!$stored || !hash_equals($stored, $otp)) {
            return false;
        }

        $this->invalidate($user);

        return true;
    }

    /**
     * Xóa OTP khỏi Redis.
     */
    public function invalidate(User $user): void
    {
        Redis::del($this->redisKey($user));
    }

    // =========================================================
    //  PRIVATE HELPERS
    // =========================================================

    private function redisKey(User $user): string
    {
        return "otp:register:{$user->id}";
    }

    private function generateOtp(): string
    {
        return str_pad(
            string: (string) random_int(0, 10 ** self::OTP_LENGTH - 1),
            length: self::OTP_LENGTH,
            pad_string: '0',
            pad_type: STR_PAD_LEFT
        );
    }
}
