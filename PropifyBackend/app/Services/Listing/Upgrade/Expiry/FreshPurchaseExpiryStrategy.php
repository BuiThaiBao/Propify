<?php

namespace App\Services\Listing\Upgrade\Expiry;

use App\DTOs\Listing\UpgradeContext;
use Carbon\CarbonInterface;

final class FreshPurchaseExpiryStrategy implements ExpiryCalculationStrategy
{
    public function calculate(UpgradeContext $context): CarbonInterface
    {
        return $context->now->copy()->addDays($context->durationDays);
    }
}
