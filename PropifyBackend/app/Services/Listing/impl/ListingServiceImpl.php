<?php

namespace App\Services\Listing\impl;

use App\DTOs\Listing\CreateListingDto;
use App\DTOs\Listing\UpgradeContext;
use App\Enums\ErrorCode;
use App\Events\Listing\ListingSaved;
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
use App\Services\Listing\Commands\UnlistListingCommand;
use App\Services\Listing\Commands\UpdateListingCommand;
use App\Services\Listing\ListingService;
use App\Services\Listing\Sorting\ListingSortingStrategyFactory;
use App\Services\Listing\State\ListingStatusStateFactory;
use App\Services\Listing\Upgrade\CreateUpgradePaymentCommand;
use App\Services\Listing\Upgrade\UpgradeEligibilityPolicy;
use App\Services\Listing\Upgrade\UpgradeListingCommand;
use App\Services\Listing\Verification\ListingVerificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
        private readonly UnlistListingCommand $unlistListingCommand,
        private readonly ListingStatusStateFactory $statusStateFactory,
        private readonly UpgradeEligibilityPolicy $upgradeEligibilityPolicy,
        private readonly UpgradeListingCommand $upgradeListingCommand,
        private readonly CreateUpgradePaymentCommand $createUpgradePaymentCommand,
        private readonly ListingVerificationService $listingVerificationService,
    ) {}

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
        $listing = $this->listingRepository->findById($id);

        if ($listing->status !== 'ACTIVE') {
            throw new BusinessException(ErrorCode::ListingNotFound);
        }

        return $listing;
    }

    public function getListingDetailsForAdmin(int $id): Listing
    {
        $listing = $this->listingRepository->findById($id);

        if ($listing->status === 'DRAFT') {
            throw new BusinessException(ErrorCode::ListingNotFound);
        }

        return $listing;
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

            if (! $this->statusStateFactory->make($listing->status)->canTransitionTo('LOCKED')) {
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

    public function unlist(User $user, int $id): Listing
    {
        return $this->unlistListingCommand->handle($user, $id);
    }

    public function updateVerification(User $user, int $id, array $payload): Listing
    {
        $updated = $this->listingVerificationService->requestVerification($user, $id, $payload);

        $this->clearPublicListingsCache();

        return $updated;
    }

    public function getPublicListings(
        ?string $sortBy,
        ?string $demandType,
        ?string $keyword,
        int $perPage,
        ?string $posterType = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?float $minArea = null,
        ?float $maxArea = null
    ): LengthAwarePaginator {
        $strategy = ListingSortingStrategyFactory::make($sortBy);
        $page = request()->input('page', 1);
        $cacheKey = 'listings:public:'.md5(serialize([
            'version' => 3,
            'sort' => $sortBy,
            'demand_type' => $demandType,
            'keyword' => $keyword,
            'per_page' => $perPage,
            'page' => $page,
            'poster_type' => $posterType,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'min_area' => $minArea,
            'max_area' => $maxArea,
        ]));

        return Cache::tags(['listings:public'])->remember($cacheKey, 300, function () use (
            $strategy, $demandType, $keyword, $perPage, $posterType, $minPrice, $maxPrice, $minArea, $maxArea
        ) {
            return $this->listingRepository->paginatePublic(
                $strategy, $demandType, $keyword, $perPage, $posterType, $minPrice, $maxPrice, $minArea, $maxArea
            );
        });
    }

    public function getAllForAdmin(?string $status, ?string $demandType, ?string $keyword, int $perPage): LengthAwarePaginator
    {
        return $this->listingRepository->paginateAdmin($status, $demandType, $keyword, $perPage);
    }

    public function getMapListings(
        ?string $demandType,
        ?string $keyword,
        ?string $posterType = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?float $minArea = null,
        ?float $maxArea = null
    ): Collection {
        $cacheKey = 'listings:public:map:'.md5(serialize([
            'version' => 2,
            'demand_type' => $demandType,
            'keyword' => $keyword,
            'poster_type' => $posterType,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'min_area' => $minArea,
            'max_area' => $maxArea,
        ]));

        return Cache::tags(['listings:public'])->remember($cacheKey, 300, fn () => $this->listingRepository->getMapListings(
            demandType: $demandType,
            keyword: $keyword,
            posterType: $posterType,
            minPrice: $minPrice,
            maxPrice: $maxPrice,
            minArea: $minArea,
            maxArea: $maxArea,
        ));
    }

    public function changeStatusForAdmin(int $listingId, string $status, ?string $rejectionReason = null, ?int $adminUserId = null): Listing
    {
        if ($adminUserId === null) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        if ($status === 'REJECTED' && trim((string) $rejectionReason) === '') {
            throw new BusinessException(ErrorCode::BadRequest, 'Vui lòng nhập lý do từ chối.');
        }

        $listing = DB::transaction(function () use ($listingId, $status, $rejectionReason, $adminUserId) {
            $listing = Listing::query()->lockForUpdate()->find($listingId);
            if (! $listing) {
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

    public function updateVerificationForAdmin(int $listingId, bool $isVerified, ?string $reason = null, ?int $adminUserId = null): Listing
    {
        if ($adminUserId === null) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $listing = $isVerified
            ? $this->listingVerificationService->approveVerification($listingId, $adminUserId)
            : $this->listingVerificationService->rejectVerification($listingId, $adminUserId, (string) $reason);

        Cache::tags(['listings:public'])->flush();

        return $listing;
    }

    public function createUpgradePayment(User $user, int $listingId, int $packageId, int $durationDays, string $clientIp): string
    {
        [$listing, $newPackage, $pricing, $context] = $this->prepareUpgrade($user, $listingId, $packageId, $durationDays);

        return $this->createUpgradePaymentCommand->execute(
            user: $user,
            listing: $listing,
            newPackage: $newPackage,
            durationDays: $durationDays,
            amount: (float) $pricing->price,
            clientIp: $clientIp,
        );
    }

    public function completePaidUpgrade(Transaction $transaction): Listing
    {
        $transaction->loadMissing('user');

        [$listing, $newPackage, $_pricing, $context] = $this->prepareUpgrade(
            $transaction->user,
            (int) $transaction->listing_id,
            (int) $transaction->package_id,
            (int) $transaction->duration_days,
        );

        return $this->upgradeListingCommand->execute(
            transaction: $transaction,
            listing: $listing,
            newPackage: $newPackage,
            context: $context,
        );
    }

    private function prepareUpgrade(User $user, int $listingId, int $packageId, int $durationDays): array
    {
        $listing = Listing::find($listingId);

        if (! $listing) {
            throw new BusinessException(ErrorCode::ListingNotFound);
        }

        $newPackage = Package::find($packageId);

        if (! $newPackage) {
            throw new BusinessException(ErrorCode::PackageNotFound);
        }

        $pricing = $newPackage->pricings()
            ->where('duration_days', $durationDays)
            ->where('is_active', true)
            ->first();

        if (! $pricing) {
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

        $this->upgradeEligibilityPolicy->assertEligible($context);

        return [$listing, $newPackage, $pricing, $context];
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
        [$listing, $newPackage, $pricing, $context] = $this->prepareUpgrade($user, $listingId, $packageId, $durationDays);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'package_id' => $newPackage->id,
            'amount' => $pricing->price,
            'duration_days' => $durationDays,
            'payment_method' => 'SIMULATED',
            'status' => 'SUCCESS',
            'transaction_date' => now(),
        ]);

        return $this->upgradeListingCommand->execute(
            transaction: $transaction,
            listing: $listing,
            newPackage: $newPackage,
            context: $context,
        );
    }

    /**
     * Tính content_score dựa trên chất lượng nội dung listing.
     *
     * | Yếu tố                        | Điểm |
     * |-------------------------------|------|
     * | Có title rõ ràng              | +10  |
     * | Có description                | +10  |
     * | Có ảnh (images)               | +20  |
     * | Có video                      | +10  |
     * | Thông tin đầy đủ (giá, diện tích, địa chỉ) | +20 |
     * | Đã xác minh (is_verified)     | +25  |
     * | Có AI description             | +5   |
     * | → Max ~100 điểm               |      |
     */
    private function calculateContentScore(CreateListingDto $dto): int
    {
        $score = 0;

        // Title rõ ràng (>= 10 ký tự)
        if (! empty($dto->title) && mb_strlen($dto->title) >= 10) {
            $score += 10;
        }

        // Có description (>= 20 ký tự)
        if (! empty($dto->description) && mb_strlen($dto->description) >= 20) {
            $score += 10;
        }

        // Có ảnh
        if (! empty($dto->images) && count($dto->images) > 0) {
            $score += 20;
        }

        // Có video
        if ($dto->video !== null) {
            $score += 10;
        }

        // Thông tin đầy đủ: giá > 0, diện tích > 0, có địa chỉ
        $hasFullInfo = ($dto->price > 0)
            && ($dto->area > 0)
            && (! empty($dto->addressDetail) || ! empty($dto->projectName));
        if ($hasFullInfo) {
            $score += 20;
        }

        // Xác minh (khi tạo mới thì chưa verified, nhưng nếu request verification thì +5 bonus)
        if ($dto->requestVerification) {
            $score += 5;
        }

        return min($score, 100);
    }

    /**
     * Xóa cache public listings khi dữ liệu thay đổi.
     */
    private function clearPublicListingsCache(): void
    {
        Cache::tags(['listings:public'])->flush();
    }
}
