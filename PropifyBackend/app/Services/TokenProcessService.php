<?php

namespace App\Services;

interface TokenProcessService
{
    public function isBlacklist(string $token): bool;
    public function addTokenToBlacklist(string $token, int $ttl): void;
    public function getBlackListKey(string $token): string;
}

