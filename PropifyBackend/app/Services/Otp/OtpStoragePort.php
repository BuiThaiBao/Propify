<?php

namespace App\Services\Otp;

/**
 * Port (Target Interface) cho OTP storage.
 *
 * Tách OtpService khỏi Redis hardcode.
 * Khi cần đổi storage (Cache, Database, ...) → chỉ tạo Adapter mới.
 * Trong test → dùng CacheOtpStorageAdapter thay vì mock Redis.
 */
interface OtpStoragePort
{
    /**
     * Lưu OTP với TTL.
     * Nếu key đã tồn tại → xóa cũ rồi ghi mới (atomic).
     */
    public function store(string $key, string $value, int $ttlSeconds): void;

    /**
     * Đọc giá trị OTP theo key. Null nếu hết hạn hoặc không tồn tại.
     */
    public function retrieve(string $key): ?string;

    /**
     * Xóa OTP khỏi storage.
     */
    public function delete(string $key): void;
}
