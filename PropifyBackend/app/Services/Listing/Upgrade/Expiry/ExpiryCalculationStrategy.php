<?php

namespace App\Services\Listing\Upgrade\Expiry;

use App\DTOs\Listing\UpgradeContext;
use Carbon\CarbonInterface;

interface ExpiryCalculationStrategy
{
    public function calculate(UpgradeContext $context): CarbonInterface;
}
