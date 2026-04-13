<?php

namespace App\Services\Otp\Adapters;

use App\Services\Otp\OtpStoragePort;
use Illuminate\Support\Facades\Cache;

/**
 * Adapter: Laravel Cache → OtpStoragePort.
 *
 * Dùng cho môi trường test hoặc khi không có Redis.
 * Laravel Cache driver có thể là array, file, database, ...
 */
final class CacheOtpStorageAdapter implements OtpStoragePort
{
    public function store(string $key, string $value, int $ttlSeconds): void
    {
        Cache::forget($key);
        Cache::put($key, $value, $ttlSeconds);
    }

    public function retrieve(string $key): ?string
    {
        return Cache::get($key);
    }

    public function delete(string $key): void
    {
        Cache::forget($key);
    }
}
