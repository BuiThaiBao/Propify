<?php

namespace App\Services\Otp\Impl;

use App\Enums\MailType;
use App\Enums\NotificationChanelType;
use App\Enums\OtpContext;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\Otp\OtpService;
use App\Services\Otp\OtpStoragePort;

final class OtpServiceImpl implements OtpService
{
    /** TTL của OTP — 5 phút (BR.ACC.05) */
    private const OTP_TTL_SECONDS = 300;

    /** Độ dài OTP */
    private const OTP_LENGTH = 6;

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly OtpStoragePort $storage, // ← inject qua interface, không hardcode Redis
    ) {}

    public function generate(User $user, OtpContext $context): string
    {
        $otp = $this->generateOtp();
        $key = $this->storageKey($user, $context);

        // Delegate cho OtpStoragePort — không biết Redis hay Cache
        $this->storage->store($key, $otp, self::OTP_TTL_SECONDS);

        $mailType = match ($context) {
            OtpContext::REGISTER       => MailType::VERIFY_EMAIL,
            OtpContext::RESET_PASSWORD => MailType::FORGOT_PASSWORD,
        };

        $this->notificationService->send(
            user: $user,
            template: $mailType,
            data: ['otp' => $otp],
            channels: [NotificationChanelType::EMAIL],
        );

        return $otp;
    }

    public function verify(User $user, string $otp, OtpContext $context): bool
    {
        $stored = $this->storage->retrieve($this->storageKey($user, $context));

        if (!$stored || !hash_equals($stored, $otp)) {
            return false;
        }

        $this->invalidate($user, $context);

        return true;
    }

    /**
     * Kiểm tra OTP hợp lệ mà KHÔNG xóa storage.
     * Step 2: check → step 3: reset (verify + xóa thật sự).
     */
    public function peek(User $user, string $otp, OtpContext $context): bool
    {
        $stored = $this->storage->retrieve($this->storageKey($user, $context));

        return $stored && hash_equals($stored, $otp);
    }

    public function invalidate(User $user, OtpContext $context): void
    {
        $this->storage->delete($this->storageKey($user, $context));
    }

    // =========================================================
    //  PRIVATE HELPERS
    // =========================================================

    private function storageKey(User $user, OtpContext $context): string
    {
        return "otp:{$context->value}:{$user->id}";
        // register → otp:register:1
        // reset    → otp:reset:1
    }

    private function generateOtp(): string
    {
        return str_pad(
            (string) random_int(0, 10 ** self::OTP_LENGTH - 1),
            self::OTP_LENGTH,
            '0',
            STR_PAD_LEFT
        );
    }
}
