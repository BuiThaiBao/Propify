<?php

namespace App\Services\Listing\Upgrade\Specifications;

use App\DTOs\Listing\UpgradeContext;

final class CanRenewSpecification
{
    public function isSatisfiedBy(UpgradeContext $context): bool
    {
        return $context->isRenewal();
    }
}
