<?php

namespace App\Services\Listing\Verification\Impl;

use App\Enums\ErrorCode;
use App\Enums\ListingVerificationStatus;
use App\Events\Listing\ListingVerificationRequested;
use App\Exceptions\BusinessException;
use App\Models\Listing;
use App\Models\ListingStatusHistory;
use App\Models\User;
use App\Repositories\ListingRepository;
use App\Services\Listing\Verification\ListingVerificationService;
use App\Support\ListingVerificationStatusResolver;
use Illuminate\Support\Facades\DB;

final class ListingVerificationServiceImpl implements ListingVerificationService
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
    ) {}

    public function requestVerification(User $user, int $listingId, array $documents): Listing
    {
        $updated = DB::transaction(function () use ($user, $listingId, $documents) {
            $listing = Listing::query()->lockForUpdate()->findOrFail($listingId);

            if ($listing->is_verified === ListingVerificationStatus::VERIFIED) {
                throw new BusinessException(ErrorCode::BadRequest, 'Tin dang nay da duoc xac thuc.');
            }

            if ((int) $listing->owner_id !== (int) $user->id) {
                throw new BusinessException(ErrorCode::AuthForbidden);
            }

            if ($listing->demand_type === 'RENT') {
                throw new BusinessException(ErrorCode::BadRequest, 'Tin cho thue khong ho tro xac thuc bat dong san.');
            }

            $identityCardFront = $documents['identity_card_front'] ?? null;
            $identityCardBack = $documents['identity_card_back'] ?? null;
            $legalDocuments = $documents['legal_documents'] ?? [];
            if (! ListingVerificationStatusResolver::hasCompleteDocuments($identityCardFront, $identityCardBack, $legalDocuments)) {
                throw new BusinessException(ErrorCode::ValidationError, 'Can tai len day du CCCD mat truoc, mat sau va it nhat mot anh giay to phap ly de gui yeu cau xac thuc.');
            }

            $requestVerification = true;

            $this->listingRepository->updateListing($listing->id, [
                'request_verification' => $requestVerification,
                'is_verified' => ListingVerificationStatusResolver::forSubmission(
                    $listing->demand_type,
                    $identityCardFront,
                    $identityCardBack,
                    $legalDocuments,
                    $listing->is_verified,
                )->value,
            ]);

            if ($listing->property_id) {
                $this->listingRepository->updateProperty($listing->property_id, [
                    'public_info_agreed' => (bool) ($documents['public_info_agreed'] ?? false),
                ]);
            }

            $this->listingRepository->deleteVerificationDocuments($listing->id);
            $sortOrder = 0;
            foreach ([
                ['ID_FRONT', $identityCardFront],
                ['ID_BACK', $identityCardBack],
            ] as [$type, $url]) {
                if ($url) {
                    $this->listingRepository->createVerificationDocument([
                        'listing_id' => $listing->id,
                        'document_type' => $type,
                        'file_url' => $url,
                        'mime_type' => 'image/jpeg',
                        'file_size' => 0,
                        'sort_order' => $sortOrder++,
                    ]);
                }
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

        event(new ListingVerificationRequested($updated->id, $user->id));

        return $updated;
    }

    public function approveVerification(int $listingId, int $adminUserId): Listing
    {
        return $this->updateVerificationStatus($listingId, true, null, $adminUserId);
    }

    public function rejectVerification(int $listingId, int $adminUserId, string $reason): Listing
    {
        return $this->updateVerificationStatus($listingId, false, $reason, $adminUserId);
    }

    private function updateVerificationStatus(int $listingId, bool $isVerified, ?string $reason, int $adminUserId): Listing
    {
        if (! $isVerified && trim((string) $reason) === '') {
            throw new BusinessException(ErrorCode::BadRequest, 'Vui lòng nhập lý do từ chối xác thực.');
        }

        $listing = DB::transaction(function () use ($listingId, $isVerified, $reason, $adminUserId) {
            $listing = Listing::query()->lockForUpdate()->find($listingId);
            if (! $listing) {
                throw new BusinessException(ErrorCode::ListingNotFound);
            }

            $listing->is_verified = $isVerified
                ? ListingVerificationStatus::VERIFIED
                : ListingVerificationStatus::REJECTED;
            $listing->save();

            ListingStatusHistory::create([
                'user_id' => $adminUserId,
                'listing_id' => $listing->id,
                'action' => $isVerified ? 'VERIFY_APPROVED' : 'VERIFY_REJECTED',
                'reason' => $isVerified ? null : $reason,
            ]);

            return $listing;
        });

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
            'statusHistories.user',
        ]);
    }
}
