<?php

namespace App\Services\Listing\Upgrade\Benefits;

use App\DTOs\Listing\UpgradeContext;

interface PackageBenefitStrategy
{
    public function apply(UpgradeContext $context): void;
}
