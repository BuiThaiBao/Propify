<?php

namespace App\Services\Listing\Upgrade\Expiry;

use App\DTOs\Listing\UpgradeContext;

final class ExpiryCalculationStrategyFactory
{
    public function __construct(
        private readonly FreshPurchaseExpiryStrategy $freshPurchaseStrategy,
        private readonly RenewalExpiryStrategy $renewalStrategy,
    ) {
    }

    public function make(UpgradeContext $context): ExpiryCalculationStrategy
    {
        return $context->isRenewal()
            ? $this->renewalStrategy
            : $this->freshPurchaseStrategy;
    }
}
