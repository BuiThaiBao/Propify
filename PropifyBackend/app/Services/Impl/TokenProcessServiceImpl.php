<?php

namespace App\Services\Impl;

use App\Services\TokenProcessService;
use Illuminate\Support\Facades\Cache;

final class TokenProcessServiceImpl implements TokenProcessService
{
    public function isBlacklist(string $token): bool
    {
        return Cache::has($this->getBlackListKey($token));
    }

    public function addTokenToBlacklist(string $token, int $ttl): void
    {
        Cache::put($this->getBlackListKey($token), true, $ttl);
    }

    public function getBlackListKey(string $token): string
    {
        return 'blacklist:' . $token;
    }
}