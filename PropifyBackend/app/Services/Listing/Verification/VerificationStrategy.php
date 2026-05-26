<?php

namespace App\Services\Listing\Verification;

use App\Models\Listing;

interface VerificationStrategy
{
    public function supports(string $method): bool;

    public function verify(Listing $listing, array $documents): VerificationResult;
}
