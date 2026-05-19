<?php

namespace App\Services\Listing\impl;

use App\DTOs\Listing\CreateListingDto;
use App\DTOs\Listing\UpgradeContext;
use App\Enums\ErrorCode;
use App\Events\Listing\ListingSaved;
use App\Events\Listing\ListingPackageUpgraded;
use App\Exceptions\BusinessException;
use App\Models\Listing;
use App\Models\ListingStatusHistory;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\ListingRepository;
use App\Services\Listing\Commands\CreateListingCommand;
use App\Services\Listing\Commands\SaveDraftListingCommand;
use App\Services\Listing\Commands\SubmitListingVerificationCommand;
use App\Services\Listing\Commands\UpdateListingCommand;
use App\Services\Listing\ListingService;
use App\Services\Listing\State\ListingStatusStateFactory;
use App\Services\Listing\Upgrade\Benefits\PackageBenefitStrategyFactory;
use App\Services\Listing\Upgrade\Expiry\ExpiryCalculationStrategyFactory;
use App\Services\Listing\Upgrade\UpgradeEligibilityPolicy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class ListingServiceImpl implements ListingService
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly CreateListingCommand $createListingCommand,
        private readonly UpdateListingCommand $updateListingCommand,
        private readonly SaveDraftListingCommand $saveDraftListingCommand,
        private readonly SubmitListingVerificationCommand $submitListingVerificationCommand,
        private readonly ListingStatusStateFactory $statusStateFactory,
        private readonly UpgradeEligibilityPolicy $upgradeEligibilityPolicy,
        private readonly ExpiryCalculationStrategyFactory $expiryStrategyFactory,
        private readonly PackageBenefitStrategyFactory $benefitStrategyFactory,
    ) {
    }

    public function create(User $user, CreateListingDto $dto): Listing
    {
        if ($dto->saveAsDraft) {
            return $this->saveDraftListingCommand->create($user, $dto);
        }

        return $this->createListingCommand->handle($user, $dto);
    }

    public function getMyListings(User $user, ?string $status, ?string $demandType, ?string $keyword, int $perPage): LengthAwarePaginator
    {
        return $this->listingRepository->paginateByOwner(
            ownerId: (int) $user->id,
            status: $status,
            demandType: $demandType,
            keyword: $keyword,
            perPage: $perPage,
        );
    }

    public function getListingDetails(int $id): Listing
    {
        return $this->listingRepository->findById($id);
    }

    public function getOwnedListingDetails(User $user, int $id): Listing
    {
        $listing = $this->listingRepository->findById($id);

        if ((int) $listing->owner_id !== (int) $user->id) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        return $listing;
    }

    public function update(User $user, int $id, CreateListingDto $dto): Listing
    {
        if ($dto->saveAsDraft) {
            return $this->saveDraftListingCommand->update($user, $id, $dto);
        }

        return $this->updateListingCommand->handle($user, $id, $dto);
    }

    public function lock(User $user, int $id): Listing
    {
        return DB::transaction(function () use ($user, $id) {
            $listing = Listing::query()->lockForUpdate()->findOrFail($id);

            if ((int) $listing->owner_id !== (int) $user->id) {
                throw new BusinessException(ErrorCode::AuthForbidden);
            }

            if ($listing->status === 'LOCKED') {
                throw new BusinessException(ErrorCode::ListingAlreadyLocked);
            }

            if (!$this->statusStateFactory->make($listing->status)->canTransitionTo('LOCKED')) {
                throw new BusinessException(ErrorCode::ListingCannotBeLocked);
            }

            $updated = $this->listingRepository->updateListing($listing->id, [
                'status' => 'LOCKED',
            ]);

            $loaded = $updated->load([
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointments',
            ]);

            ListingSaved::dispatch($loaded, $user, 'locked');

            return $loaded;
        });
    }

    public function updateVerification(User $user, int $id, array $payload): Listing
    {
        return $this->submitListingVerificationCommand->handle($user, $id, $payload);
    }

    public function getPublicListings(?string $demandType, ?string $keyword, int $perPage): LengthAwarePaginator
    {
        $page = request()->input('page', 1);
        $cacheKey = 'listings:public:' . md5(serialize([
            'demand_type' => $demandType,
            'keyword'     => $keyword,
            'per_page'    => $perPage,
            'page'        => $page,
        ]));

        return Cache::tags(['listings:public'])->remember($cacheKey, 300, function () use ($demandType, $keyword, $perPage) {
            return $this->listingRepository->paginatePublic($demandType, $keyword, $perPage);
        });
    }

    public function getAllForAdmin(?string $status, ?string $demandType, ?string $keyword, int $perPage): LengthAwarePaginator
    {
        return $this->listingRepository->paginateAdmin($status, $demandType, $keyword, $perPage);
    }

    public function changeStatusForAdmin(int $listingId, string $status, ?string $rejectionReason = null, ?int $adminUserId = null): Listing
    {
        if ($adminUserId === null) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $listing = DB::transaction(function () use ($listingId, $status, $rejectionReason, $adminUserId) {
            $listing = Listing::query()->lockForUpdate()->find($listingId);
            if (!$listing) {
                throw new BusinessException(ErrorCode::ListingNotFound);
            }
            $this->statusStateFactory->assertCanTransition($listing->status, $status);

            $listing->status = $status;
            if ($status === 'REJECTED') {
                $listing->rejection_reason = $rejectionReason;
            }

            if ($status === 'ACTIVE') {
                $listing->published_at = now();
                $listing->approved_by = $adminUserId;
            }

            $listing->save();

            ListingStatusHistory::create([
                'user_id' => $adminUserId,
                'listing_id' => $listing->id,
                'action' => $status,
                'reason' => $rejectionReason,
            ]);

            return $listing;
        });
        $loaded = $listing->load([
            'property.attributes.group',
            'images',
            'videos',
            'verificationDocuments',
            'appointmentSlots',
            'appointments',
            'owner',
            'approver',
            'package',
        ]);

        ListingSaved::dispatch($loaded, null, 'admin_status_changed');

        return $loaded;
    }

    /**
     * Nâng cấp gói tin cho listing.
     *
     * Flow: Verify ownership → Verify listing ACTIVE → Verify package → Lookup pricing
     *       → Check upgrade direction → DB Transaction: Create Transaction + Update listing
     *       → Clear cache → Return updated listing
     *
     * Gia hạn: Nếu listing đang có gói chưa hết hạn và user mua lại cùng gói,
     *          thời hạn mới sẽ CỘNG THÊM vào thời hạn còn lại.
     */
    public function upgradeListing(User $user, int $listingId, int $packageId, int $durationDays): Listing
    {
        // 1. Load entities required for the upgrade flow.
        $listing = Listing::find($listingId);

        if (!$listing) {
            throw new BusinessException(ErrorCode::ListingNotFound);
        }

        $newPackage = Package::find($packageId);

        if (!$newPackage) {
            throw new BusinessException(ErrorCode::PackageNotFound);
        }

        $pricing = $newPackage->pricings()
            ->where('duration_days', $durationDays)
            ->where('is_active', true)
            ->first();

        if (!$pricing) {
            throw new BusinessException(ErrorCode::PackagePricingNotFound);
        }

        $currentPackage = $listing->package_id
            ? Package::find($listing->package_id)
            : null;

        $context = new UpgradeContext(
            user: $user,
            listing: $listing,
            newPackage: $newPackage,
            pricing: $pricing,
            durationDays: $durationDays,
            currentPackage: $currentPackage,
            now: now(),
        );

        // 2. Apply business policy and strategy selection.
        $this->upgradeEligibilityPolicy->assertEligible($context);
        $expiresAt = $this->expiryStrategyFactory->make($context)->calculate($context);
        $benefitStrategy = $this->benefitStrategyFactory->make($newPackage);

        // 3. Commit core upgrade state atomically.
        $updated = DB::transaction(function () use ($user, $listing, $newPackage, $pricing, $expiresAt, $benefitStrategy, $context) {
            // Tạo transaction (giả lập thanh toán — auto SUCCESS)
            Transaction::create([
                'user_id'          => $user->id,
                'listing_id'       => $listing->id,
                'package_id'       => $newPackage->id,
                'amount'           => $pricing->price,
                'payment_method'   => 'SIMULATED',
                'status'           => 'SUCCESS',
                'transaction_date' => now(),
            ]);

            // Gắn gói mới + set ngày hết hạn
            $listing->update([
                'package_id'         => $newPackage->id,
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
            listing: $updated,
            user: $user,
            oldPackage: $currentPackage,
            newPackage: $newPackage,
            durationDays: $durationDays,
            amount: (string) $pricing->price,
            expiresAt: $expiresAt,
            isRenewal: $context->isRenewal(),
        );

        return $updated;
    }


}
