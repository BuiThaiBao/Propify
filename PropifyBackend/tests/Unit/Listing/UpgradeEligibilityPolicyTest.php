<?php

namespace Tests\Unit\Listing;

use App\DTOs\Listing\UpgradeContext;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\Listing;
use App\Models\Package;
use App\Models\PackagePricing;
use App\Models\User;
use App\Services\Listing\Upgrade\Specifications\CanRenewSpecification;
use App\Services\Listing\Upgrade\Specifications\CanUpgradeSpecification;
use App\Services\Listing\Upgrade\UpgradeEligibilityPolicy;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class UpgradeEligibilityPolicyTest extends TestCase
{
    public function test_allows_renewal_for_same_package(): void
    {
        $context = $this->context(
            listingPackageId: 2,
            currentPackagePriority: 2,
            newPackageId: 2,
            newPackagePriority: 2,
        );

        $this->policy()->assertEligible($context);

        $this->assertTrue(true);
    }

    public function test_allows_upgrade_to_higher_priority_package(): void
    {
        $context = $this->context(
            listingPackageId: 2,
            currentPackagePriority: 2,
            newPackageId: 3,
            newPackagePriority: 3,
        );

        $this->policy()->assertEligible($context);

        $this->assertTrue(true);
    }

    public function test_rejects_downgrade(): void
    {
        $context = $this->context(
            listingPackageId: 3,
            currentPackagePriority: 3,
            newPackageId: 2,
            newPackagePriority: 2,
        );

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage(ErrorCode::ListingUpgradeNotAllowed->message());

        $this->policy()->assertEligible($context);
    }

    public function test_rejects_non_owner(): void
    {
        $context = $this->context(
            listingOwnerId: 99,
            listingPackageId: 2,
            currentPackagePriority: 2,
            newPackageId: 3,
            newPackagePriority: 3,
        );

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage(ErrorCode::ListingNotOwned->message());

        $this->policy()->assertEligible($context);
    }

    public function test_rejects_inactive_package(): void
    {
        $context = $this->context(
            listingPackageId: 2,
            currentPackagePriority: 2,
            newPackageId: 3,
            newPackagePriority: 3,
            newPackageActive: false,
        );

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage(ErrorCode::PackageInactive->message());

        $this->policy()->assertEligible($context);
    }

    private function policy(): UpgradeEligibilityPolicy
    {
        return new UpgradeEligibilityPolicy(
            new CanRenewSpecification(),
            new CanUpgradeSpecification(),
        );
    }

    private function context(
        int $listingOwnerId = 1,
        int $listingPackageId = 1,
        int $currentPackagePriority = 1,
        int $newPackageId = 2,
        int $newPackagePriority = 2,
        bool $newPackageActive = true,
    ): UpgradeContext {
        $user = new User();
        $user->id = 1;

        $listing = new Listing();
        $listing->owner_id = $listingOwnerId;
        $listing->status = 'ACTIVE';
        $listing->package_id = $listingPackageId;

        $currentPackage = new Package();
        $currentPackage->id = $listingPackageId;
        $currentPackage->priority = $currentPackagePriority;

        $newPackage = new Package();
        $newPackage->id = $newPackageId;
        $newPackage->priority = $newPackagePriority;
        $newPackage->is_active = $newPackageActive;

        $pricing = new PackagePricing();
        $pricing->duration_days = 7;
        $pricing->price = '7000.00';

        return new UpgradeContext(
            user: $user,
            listing: $listing,
            newPackage: $newPackage,
            pricing: $pricing,
            durationDays: 7,
            currentPackage: $currentPackage,
            now: Carbon::parse('2026-05-15 10:00:00'),
        );
    }
}
