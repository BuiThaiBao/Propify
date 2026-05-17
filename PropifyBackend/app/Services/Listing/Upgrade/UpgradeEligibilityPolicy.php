<?php

namespace App\Services\Listing\Upgrade;

use App\DTOs\Listing\UpgradeContext;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Services\Listing\Upgrade\Specifications\CanRenewSpecification;
use App\Services\Listing\Upgrade\Specifications\CanUpgradeSpecification;

final class UpgradeEligibilityPolicy
{
    public function __construct(
        private readonly CanRenewSpecification $canRenew,
        private readonly CanUpgradeSpecification $canUpgrade,
    ) {
    }

    public function assertEligible(UpgradeContext $context): void
    {
        if ($context->listing->owner_id !== $context->user->id) {
            throw new BusinessException(ErrorCode::ListingNotOwned);
        }

        if ($context->listing->status !== 'ACTIVE') {
            throw new BusinessException(ErrorCode::ListingUpgradeNotAllowed);
        }

        if (!$context->newPackage->is_active) {
            throw new BusinessException(ErrorCode::PackageInactive);
        }

        if (!$this->canRenew->isSatisfiedBy($context) && !$this->canUpgrade->isSatisfiedBy($context)) {
            throw new BusinessException(ErrorCode::ListingUpgradeNotAllowed);
        }
    }
}
