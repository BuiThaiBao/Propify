<?php

namespace App\Services\Listing\Upgrade\Expiry;

use App\DTOs\Listing\UpgradeContext;
use Carbon\CarbonInterface;

final class RenewalExpiryStrategy implements ExpiryCalculationStrategy
{
    public function calculate(UpgradeContext $context): CarbonInterface
    {
        $baseTime = $context->listing->package_expires_at;

        if (!$baseTime || !$baseTime->isFuture()) {
            $baseTime = $context->now;
        }

        return $baseTime->copy()->addDays($context->durationDays);
    }
}
