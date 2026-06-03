<?php

namespace App\Services\Listing\Upgrade;

use App\DTOs\Listing\UpgradeContext;
use App\Events\Listing\ListingPackageUpgraded;
use App\Models\Listing;
use App\Models\Package;
use App\Models\Transaction;
use App\Services\Listing\Upgrade\Benefits\PackageBenefitStrategyFactory;
use App\Services\Listing\Upgrade\Expiry\ExpiryCalculationStrategyFactory;
use Illuminate\Support\Facades\DB;

final class UpgradeListingCommand
{
    public function __construct(
        private readonly UpgradeEligibilityPolicy $policy,
        private readonly ExpiryCalculationStrategyFactory $expiryFactory,
        private readonly PackageBenefitStrategyFactory $benefitFactory,
    ) {}

    public function execute(Transaction $transaction, Listing $listing, Package $newPackage, UpgradeContext $context): Listing
    {
        $this->policy->assertEligible($context);
        $expiresAt = $this->expiryFactory->make($context)->calculate($context);
        $benefitStrategy = $this->benefitFactory->make($newPackage);

        $updated = DB::transaction(function () use ($transaction, $listing, $newPackage, $expiresAt, $benefitStrategy, $context) {
            $transaction->update([
                'status' => 'SUCCESS',
                'transaction_date' => now(),
            ]);

            $listing->update([
                'package_id' => $newPackage->id,
                'package_expires_at' => $expiresAt,
            ]);

            $benefitStrategy->apply($context);

            return $listing->fresh([
                'property',
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointments',
                'package',
            ]);
        });

        ListingPackageUpgraded::dispatch(
            listingId: $updated->id,
            userId: $transaction->user_id,
            oldPackageId: $context->currentPackage?->id,
            newPackageId: $newPackage->id,
            durationDays: (int) $transaction->duration_days,
            amount: (string) $transaction->amount,
            expiresAt: $expiresAt,
            isRenewal: $context->isRenewal(),
        );

        return $updated;
    }
}
