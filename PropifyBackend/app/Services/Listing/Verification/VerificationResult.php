<?php

namespace App\Services\Listing\Verification;

final class VerificationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly ?string $reason = null
    ) {}
}
