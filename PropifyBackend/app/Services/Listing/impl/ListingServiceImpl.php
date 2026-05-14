<?php

namespace App\Services\Listing\impl;

use App\DTOs\Listing\CreateListingDto;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\Listing;
use App\Models\ListingStatusHistory;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\ListingRepository;
use App\Services\Listing\ListingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ListingServiceImpl implements ListingService
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
    ) {
    }

    public function create(User $user, CreateListingDto $dto): Listing
    {
        Log::debug('[ListingService] create() called', [
            'user_id' => $user->id,
            'user_phone' => $user->phone,
            'demand_type' => $dto->demandType,
            'title' => $dto->title,
            'images_count' => count($dto->images),
            'images' => $dto->images,
            'has_video' => $dto->video !== null,
            'request_verification' => $dto->requestVerification,
        ]);

        if (!$user->phone || trim((string) $user->phone) === '') {
            Log::warning('[ListingService] Blocked: user has no phone', ['user_id' => $user->id]);
            throw new BusinessException(ErrorCode::AuthPhoneNotVerified);
        }

        $listing = DB::transaction(function () use ($user, $dto) {
            Log::debug('[ListingService] DB transaction started');
            $property = $this->listingRepository->createProperty([
                'type' => $dto->propertyType,
                'province_code' => $dto->provinceCode,
                'ward_code' => $dto->wardCode,
                'street_code' => $dto->streetCode,
                'project_name' => $dto->projectName,
                'address_detail' => $dto->addressDetail,
                'area' => $dto->area,
                'price' => $dto->price,
                'is_negotiable' => $dto->isNegotiable,
                'bedrooms' => $dto->bedrooms,
                'bathrooms' => $dto->bathrooms,
                'floors' => $dto->floors,
                'floor_number' => $dto->floorNumber,
                'balconies' => $dto->balconies,
                'facade_width' => $dto->facadeWidth,
                'depth' => $dto->depth,
                'road_width' => $dto->roadWidth,
                'direction_code' => $dto->directionCode,
                'balcony_direction_code' => $dto->balconyDirectionCode,
                'furniture_status' => $dto->furnitureStatus,
                'contact_name' => $dto->contactName,
                'contact_phone' => $dto->contactPhone,
                'contact_email' => $dto->contactEmail,
                'poster_type' => $dto->posterType,
                'amenities' => $dto->amenities,
                'legal_paper_types' => $dto->legalPaperTypes,
                'public_info_agreed' => $dto->publicInfoAgreed,
                'lat' => $dto->lat,
                'lng' => $dto->lng,
                'meta' => [
                    'source' => 'create-listing-form',
                ],
            ]);

            Log::debug('[ListingService] Property created', ['property_id' => $property->id]);

            if ($dto->attributeIds !== []) {
                $property->attributes()->sync($dto->attributeIds);
                Log::debug('[ListingService] Attributes synced', ['attribute_ids' => $dto->attributeIds]);
            }

            $listingStatus = $dto->saveAsDraft ? 'DRAFT' : 'PENDING';

            $listing = $this->listingRepository->createListing([
                'property_id' => $property->id,
                'owner_id' => $user->id,
                'demand_type' => $dto->demandType,
                'title' => $dto->title,
                'description' => $dto->description,
                'status' => $listingStatus,
                'package_id' => $dto->packageId,
                'score' => $this->calculateContentScore($dto),
                'is_verified' => false,
                'has_video' => $dto->video !== null,
                'request_verification' => $dto->requestVerification,
                'rent_min_term' => $dto->rentMinTerm,
                'rent_payment_interval' => $dto->rentPaymentInterval,
                'rent_deposit' => $dto->rentDeposit,
                'appointment_at' => $dto->appointmentAt,
                'appointment_days' => $dto->appointmentDays,
                'appointment_time_slot' => $dto->appointmentTimeSlot,
                'appointment_contact_name' => $dto->appointmentContactName,
                'appointment_contact_phone' => $dto->appointmentContactPhone,
                'appointment_contact_email' => $dto->appointmentContactEmail,
                'appointment_note' => $dto->appointmentNote,
                'submitted_at' => $dto->saveAsDraft ? null : now(),
            ]);

            Log::debug('[ListingService] Listing created', ['listing_id' => $listing->id, 'status' => $listingStatus]);

            Log::debug('[ListingService] Saving images', ['count' => count($dto->images)]);
            foreach ($dto->images as $index => $imageUrl) {
                $this->listingRepository->createListingImage([
                    'listing_id' => $listing->id,
                    'image_url' => $imageUrl,
                    'is_thumbnail' => $index === 0,
                    'sort_order' => $index,
                ]);
                Log::debug('[ListingService] Image saved', ['index' => $index, 'url' => $imageUrl]);
            }

            if ($dto->video !== null) {
                Log::debug('[ListingService] Saving video', ['url' => $dto->video]);
                $this->listingRepository->createListingVideo([
                    'listing_id' => $listing->id,
                    'video_url' => $dto->video,
                    'provider' => 'CLOUDINARY',
                    'mime_type' => 'video/mp4', // Defaulting as mime_type is no longer easily extracted
                    'file_size' => 0, // Cannot get file size easily, so 0 or nullable if DB allows
                ]);
            }

            if ($dto->requestVerification) {
                Log::debug('[ListingService] Saving verification documents', [
                    'has_id_front' => $dto->identityCardFront !== null,
                    'has_id_back' => $dto->identityCardBack !== null,
                    'legal_doc_count' => count($dto->legalDocuments),
                ]);
                $sortOrder = 0;

                if ($dto->identityCardFront !== null) {
                    $this->listingRepository->createVerificationDocument([
                        'listing_id' => $listing->id,
                        'document_type' => 'ID_FRONT',
                        'file_url' => $dto->identityCardFront,
                        'mime_type' => 'image/jpeg',
                        'file_size' => 0,
                        'sort_order' => $sortOrder++,
                    ]);
                }

                if ($dto->identityCardBack !== null) {
                    $this->listingRepository->createVerificationDocument([
                        'listing_id' => $listing->id,
                        'document_type' => 'ID_BACK',
                        'file_url' => $dto->identityCardBack,
                        'mime_type' => 'image/jpeg',
                        'file_size' => 0,
                        'sort_order' => $sortOrder++,
                    ]);
                }

                foreach ($dto->legalDocuments as $documentUrl) {
                    $this->listingRepository->createVerificationDocument([
                        'listing_id' => $listing->id,
                        'document_type' => 'LEGAL_DOCUMENT',
                        'file_url' => $documentUrl,
                        'mime_type' => 'image/jpeg',
                        'file_size' => 0,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            Log::debug('[ListingService] Transaction complete, loading relations', ['listing_id' => $listing->id]);

            return $listing->load([
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointments',
            ]);
        });

        $this->clearPublicListingsCache();

        return $listing;
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

    public function update(User $user, int $id, CreateListingDto $dto): Listing
    {
        $listing = $this->listingRepository->findById($id);

        if ((int) $listing->owner_id !== (int) $user->id) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $updated = DB::transaction(function () use ($listing, $dto) {
            // Update property
            $this->listingRepository->updateProperty($listing->property_id, [
                'type' => $dto->propertyType,
                'province_code' => $dto->provinceCode,
                'ward_code' => $dto->wardCode,
                'street_code' => $dto->streetCode,
                'project_name' => $dto->projectName,
                'address_detail' => $dto->addressDetail,
                'area' => $dto->area,
                'price' => $dto->price,
                'is_negotiable' => $dto->isNegotiable,
                'bedrooms' => $dto->bedrooms,
                'bathrooms' => $dto->bathrooms,
                'floors' => $dto->floors,
                'floor_number' => $dto->floorNumber,
                'balconies' => $dto->balconies,
                'facade_width' => $dto->facadeWidth,
                'depth' => $dto->depth,
                'road_width' => $dto->roadWidth,
                'direction_code' => $dto->directionCode,
                'balcony_direction_code' => $dto->balconyDirectionCode,
                'furniture_status' => $dto->furnitureStatus,
                'contact_name' => $dto->contactName,
                'contact_phone' => $dto->contactPhone,
                'contact_email' => $dto->contactEmail,
                'poster_type' => $dto->posterType,
                'amenities' => $dto->amenities,
                'legal_paper_types' => $dto->legalPaperTypes,
                'public_info_agreed' => $dto->publicInfoAgreed,
                'lat' => $dto->lat,
                'lng' => $dto->lng,
            ]);

            if ($dto->attributeIds !== []) {
                $listing->property->attributes()->sync($dto->attributeIds);
            }

            // Update listing — reset status to PENDING
            $listingStatus = $dto->saveAsDraft ? 'DRAFT' : 'PENDING';

            $this->listingRepository->updateListing($listing->id, [
                'demand_type' => $dto->demandType,
                'title' => $dto->title,
                'description' => $dto->description,
                'status' => $listingStatus,
                'has_video' => $dto->video !== null,
                'request_verification' => $dto->requestVerification,
                'rent_min_term' => $dto->rentMinTerm,
                'rent_payment_interval' => $dto->rentPaymentInterval,
                'rent_deposit' => $dto->rentDeposit,
                'appointment_days' => $dto->appointmentDays,
                'appointment_time_slot' => $dto->appointmentTimeSlot,
                'appointment_contact_name' => $dto->appointmentContactName,
                'appointment_contact_phone' => $dto->appointmentContactPhone,
                'appointment_contact_email' => $dto->appointmentContactEmail,
                'appointment_note' => $dto->appointmentNote,
                'submitted_at' => $dto->saveAsDraft ? null : now(),
            ]);

            // Replace images
            $this->listingRepository->deleteListingImages($listing->id);
            foreach ($dto->images as $index => $imageUrl) {
                $this->listingRepository->createListingImage([
                    'listing_id' => $listing->id,
                    'image_url' => $imageUrl,
                    'is_thumbnail' => $index === 0,
                    'sort_order' => $index,
                ]);
            }

            // Replace videos
            $this->listingRepository->deleteListingVideos($listing->id);
            if ($dto->video !== null) {
                $this->listingRepository->createListingVideo([
                    'listing_id' => $listing->id,
                    'video_url' => $dto->video,
                    'provider' => 'CLOUDINARY',
                    'mime_type' => 'video/mp4',
                    'file_size' => 0,
                ]);
            }

            // Replace verification documents
            if ($dto->requestVerification) {
                $this->listingRepository->deleteVerificationDocuments($listing->id);
                $sortOrder = 0;
                if ($dto->identityCardFront !== null) {
                    $this->listingRepository->createVerificationDocument([
                        'listing_id' => $listing->id,
                        'document_type' => 'ID_FRONT',
                        'file_url' => $dto->identityCardFront,
                        'mime_type' => 'image/jpeg',
                        'file_size' => 0,
                        'sort_order' => $sortOrder++,
                    ]);
                }
                if ($dto->identityCardBack !== null) {
                    $this->listingRepository->createVerificationDocument([
                        'listing_id' => $listing->id,
                        'document_type' => 'ID_BACK',
                        'file_url' => $dto->identityCardBack,
                        'mime_type' => 'image/jpeg',
                        'file_size' => 0,
                        'sort_order' => $sortOrder++,
                    ]);
                }
                foreach ($dto->legalDocuments as $documentUrl) {
                    $this->listingRepository->createVerificationDocument([
                        'listing_id' => $listing->id,
                        'document_type' => 'LEGAL_DOCUMENT',
                        'file_url' => $documentUrl,
                        'mime_type' => 'image/jpeg',
                        'file_size' => 0,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            return $listing->fresh()->load([
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointments',
            ]);
        });

        $this->clearPublicListingsCache();

        return $updated;
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

            if ($listing->status !== 'ACTIVE') {
                throw new BusinessException(ErrorCode::ListingCannotBeLocked);
            }

            $updated = $this->listingRepository->updateListing($listing->id, [
                'status' => 'LOCKED',
            ]);

            return $updated->load([
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointments',
            ]);
        });
    }

    public function updateVerification(User $user, int $id, array $payload): Listing
    {
        $updated = DB::transaction(function () use ($user, $id, $payload) {
            $listing = Listing::query()->lockForUpdate()->findOrFail($id);

            if ((int) $listing->owner_id !== (int) $user->id) {
                throw new BusinessException(ErrorCode::AuthForbidden);
            }

            $identityCardFront = $payload['identity_card_front'] ?? null;
            $identityCardBack = $payload['identity_card_back'] ?? null;
            $legalDocuments = $payload['legal_documents'] ?? [];
            $requestVerification = (bool) ($identityCardFront || $identityCardBack || count($legalDocuments) > 0);

            $this->listingRepository->updateListing($listing->id, [
                'request_verification' => $requestVerification,
            ]);

            if ($listing->property_id) {
                $this->listingRepository->updateProperty($listing->property_id, [
                    'public_info_agreed' => (bool) ($payload['public_info_agreed'] ?? false),
                ]);
            }

            $this->listingRepository->deleteVerificationDocuments($listing->id);
            $sortOrder = 0;

            if ($identityCardFront) {
                $this->listingRepository->createVerificationDocument([
                    'listing_id' => $listing->id,
                    'document_type' => 'ID_FRONT',
                    'file_url' => $identityCardFront,
                    'mime_type' => 'image/jpeg',
                    'file_size' => 0,
                    'sort_order' => $sortOrder++,
                ]);
            }

            if ($identityCardBack) {
                $this->listingRepository->createVerificationDocument([
                    'listing_id' => $listing->id,
                    'document_type' => 'ID_BACK',
                    'file_url' => $identityCardBack,
                    'mime_type' => 'image/jpeg',
                    'file_size' => 0,
                    'sort_order' => $sortOrder++,
                ]);
            }

            foreach ($legalDocuments as $documentUrl) {
                $this->listingRepository->createVerificationDocument([
                    'listing_id' => $listing->id,
                    'document_type' => 'LEGAL_DOCUMENT',
                    'file_url' => $documentUrl,
                    'mime_type' => 'image/jpeg',
                    'file_size' => 0,
                    'sort_order' => $sortOrder++,
                ]);
            }

            return $listing->fresh()->load([
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointments',
            ]);
        });

        $this->clearPublicListingsCache();

        return $updated;
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

            $currentStatus = $listing->status;
            $allowedTransitions = [
                'PENDING' => ['ACTIVE', 'REJECTED'],
                'ACTIVE' => ['LOCKED', 'REJECTED'],
                'LOCKED' => ['ACTIVE'],
                'REJECTED' => ['ACTIVE'],
            ];

            if ($currentStatus === $status || !isset($allowedTransitions[$currentStatus]) || !in_array($status, $allowedTransitions[$currentStatus], true)) {
                throw new \App\Exceptions\BusinessException(\App\Enums\ErrorCode::BadRequest, "Trạng thái hiện tại của listing không hỗ trợ chuyển sang trạng thái {$status}.");
            }

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

        // Xóa cache danh sách tin công khai
        Cache::tags(['listings:public'])->flush();

        return $listing->load([
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
        // 1. Lấy listing + verify ownership
        $listing = Listing::find($listingId);

        if (!$listing) {
            throw new BusinessException(ErrorCode::ListingNotFound);
        }

        if ($listing->owner_id !== $user->id) {
            throw new BusinessException(ErrorCode::ListingNotOwned);
        }

        if ($listing->status !== 'ACTIVE') {
            throw new BusinessException(ErrorCode::ListingUpgradeNotAllowed);
        }

        // 2. Verify package tồn tại và đang active
        $newPackage = Package::find($packageId);

        if (!$newPackage) {
            throw new BusinessException(ErrorCode::PackageNotFound);
        }

        if (!$newPackage->is_active) {
            throw new BusinessException(ErrorCode::PackageInactive);
        }

        // 3. Lookup pricing — BẮT BUỘC phải có pricing
        $pricing = $newPackage->pricings()
            ->where('duration_days', $durationDays)
            ->where('is_active', true)
            ->first();

        if (!$pricing) {
            throw new BusinessException(ErrorCode::PackagePricingNotFound);
        }

        // 4. Verify upgrade direction (chỉ cho nâng cấp hoặc gia hạn cùng gói)
        $currentPriority = 0;
        $isRenewal = false;

        if ($listing->package_id) {
            $currentPackage = Package::find($listing->package_id);
            $currentPriority = $currentPackage?->priority ?? 0;

            // Cho phép gia hạn cùng gói
            if ($listing->package_id === $packageId) {
                $isRenewal = true;
            }
        }

        if (!$isRenewal && $newPackage->priority <= $currentPriority) {
            throw new BusinessException(ErrorCode::ListingUpgradeNotAllowed);
        }

        // 5. Tính package_expires_at
        //    - Gia hạn: cộng thêm ngày vào thời hạn còn lại
        //    - Mua mới: tính từ bây giờ
        $baseTime = now();
        if ($isRenewal && $listing->package_expires_at && $listing->package_expires_at->isFuture()) {
            $baseTime = $listing->package_expires_at;
        }
        $expiresAt = $baseTime->copy()->addDays($durationDays);

        // 6. DB Transaction: Tạo transaction + update listing
        $updated = DB::transaction(function () use ($user, $listing, $newPackage, $pricing, $expiresAt) {
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

        $this->clearPublicListingsCache();

        Log::info('[ListingService] Listing upgraded', [
            'listing_id'   => $listingId,
            'user_id'      => $user->id,
            'package'      => $newPackage->slug,
            'duration'     => $durationDays . ' days',
            'amount'       => $pricing->price,
            'expires_at'   => $expiresAt->toIso8601String(),
            'is_renewal'   => $isRenewal,
        ]);

        return $updated;
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
        if (!empty($dto->title) && mb_strlen($dto->title) >= 10) {
            $score += 10;
        }

        // Có description (>= 20 ký tự)
        if (!empty($dto->description) && mb_strlen($dto->description) >= 20) {
            $score += 10;
        }

        // Có ảnh
        if (!empty($dto->images) && count($dto->images) > 0) {
            $score += 20;
        }

        // Có video
        if ($dto->video !== null) {
            $score += 10;
        }

        // Thông tin đầy đủ: giá > 0, diện tích > 0, có địa chỉ
        $hasFullInfo = ($dto->price > 0)
            && ($dto->area > 0)
            && (!empty($dto->addressDetail) || !empty($dto->projectName));
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
