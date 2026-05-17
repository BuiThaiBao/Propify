<?php

namespace App\Services\Listing\Upgrade\Benefits;

use App\DTOs\Listing\UpgradeContext;

final class DataDrivenBenefitStrategy implements PackageBenefitStrategy
{
    public function apply(UpgradeContext $context): void
    {
        // Current packages are fully data-driven via DB fields such as
        // priority, multiplier, decay_rate and daily_quota.
    }
}
