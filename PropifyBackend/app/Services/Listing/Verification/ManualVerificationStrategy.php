<?php

namespace App\Services\Listing\Verification;

use App\Models\Listing;

final class ManualVerificationStrategy implements VerificationStrategy
{
    public function supports(string $method): bool
    {
        return $method === 'manual';
    }

    public function verify(Listing $listing, array $documents): VerificationResult
    {
        return new VerificationResult(
            isValid: count($documents) > 0,
            reason: count($documents) > 0 ? null : 'No verification documents provided.',
        );
    }
}
