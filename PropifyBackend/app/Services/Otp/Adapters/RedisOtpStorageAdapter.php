<?php

namespace App\Services\Otp\Adapters;

use App\Services\Otp\OtpStoragePort;
use Illuminate\Support\Facades\Redis;

/**
 * Adapter: Redis → OtpStoragePort.
 *
 * Bọc Redis Facade và expose interface domain.
 * Dùng pipeline để DEL + SETEX là atomic batch.
 */
final class RedisOtpStorageAdapter implements OtpStoragePort
{
    public function store(string $key, string $value, int $ttlSeconds): void
    {
        // Pipeline đảm bảo xóa key cũ + ghi mới là một batch nguyên tử
        // → tránh trường hợp user dùng OTP cũ khi vừa yêu cầu lại
        Redis::pipeline(function ($pipe) use ($key, $value, $ttlSeconds) {
            $pipe->del($key);
            $pipe->setex($key, $ttlSeconds, $value);
        });
    }

    public function retrieve(string $key): ?string
    {
        return Redis::get($key);
    }

    public function delete(string $key): void
    {
        Redis::del($key);
    }
}
