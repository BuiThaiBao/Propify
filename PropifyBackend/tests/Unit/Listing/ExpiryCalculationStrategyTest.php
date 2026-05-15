<?php

namespace Tests\Unit\Listing;

use App\DTOs\Listing\UpgradeContext;
use App\Models\Listing;
use App\Models\Package;
use App\Models\PackagePricing;
use App\Models\User;
use App\Services\Listing\Upgrade\Expiry\ExpiryCalculationStrategyFactory;
use App\Services\Listing\Upgrade\Expiry\FreshPurchaseExpiryStrategy;
use App\Services\Listing\Upgrade\Expiry\RenewalExpiryStrategy;
use Carbon\Carbon;
use Tests\TestCase;

final class ExpiryCalculationStrategyTest extends TestCase
{
    public function test_fresh_purchase_uses_current_time_as_base(): void
    {
        $context = $this->context(
            listingPackageId: 1,
            newPackageId: 2,
            expiresAt: Carbon::parse('2026-05-20 10:00:00'),
        );

        $strategy = $this->factory()->make($context);

        $this->assertInstanceOf(FreshPurchaseExpiryStrategy::class, $strategy);
        $this->assertSame('2026-05-22 10:00:00', $strategy->calculate($context)->format('Y-m-d H:i:s'));
    }

    public function test_active_renewal_extends_from_existing_expiry(): void
    {
        $context = $this->context(
            listingPackageId: 2,
            newPackageId: 2,
            expiresAt: Carbon::parse('2026-05-20 10:00:00'),
        );

        $strategy = $this->factory()->make($context);

        $this->assertInstanceOf(RenewalExpiryStrategy::class, $strategy);
        $this->assertSame('2026-05-27 10:00:00', $strategy->calculate($context)->format('Y-m-d H:i:s'));
    }

    public function test_expired_renewal_restarts_from_now(): void
    {
        $context = $this->context(
            listingPackageId: 2,
            newPackageId: 2,
            expiresAt: Carbon::parse('2026-05-10 10:00:00'),
        );

        $strategy = $this->factory()->make($context);

        $this->assertSame('2026-05-22 10:00:00', $strategy->calculate($context)->format('Y-m-d H:i:s'));
    }

    private function factory(): ExpiryCalculationStrategyFactory
    {
        return new ExpiryCalculationStrategyFactory(
            new FreshPurchaseExpiryStrategy(),
            new RenewalExpiryStrategy(),
        );
    }

    private function context(int $listingPackageId, int $newPackageId, Carbon $expiresAt): UpgradeContext
    {
        $user = new User();
        $user->id = 1;

        $listing = new Listing();
        $listing->owner_id = 1;
        $listing->status = 'ACTIVE';
        $listing->package_id = $listingPackageId;
        $listing->package_expires_at = $expiresAt;

        $currentPackage = new Package();
        $currentPackage->id = $listingPackageId;
        $currentPackage->priority = 1;

        $newPackage = new Package();
        $newPackage->id = $newPackageId;
        $newPackage->priority = 2;
        $newPackage->is_active = true;

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
