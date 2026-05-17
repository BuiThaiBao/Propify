<?php

namespace App\Services\Listing\Upgrade\Specifications;

use App\DTOs\Listing\UpgradeContext;

final class CanUpgradeSpecification
{
    public function isSatisfiedBy(UpgradeContext $context): bool
    {
        if ($context->isRenewal()) {
            return false;
        }

        $currentPriority = $context->currentPackage?->priority ?? 0;

        return $context->newPackage->priority > $currentPriority;
    }
}
