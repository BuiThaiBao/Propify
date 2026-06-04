<?php

namespace App\Services\Listing\Commands;

use App\Enums\ErrorCode;
use App\Events\Listing\ListingSaved;
use App\Exceptions\BusinessException;
use App\Models\Listing;
use App\Models\User;
use App\Repositories\ListingRepository;
use App\Support\ListingVerificationStatusResolver;
use Illuminate\Support\Facades\DB;

final class SubmitListingVerificationCommand
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
    ) {}

    public function handle(User $user, int $id, array $payload): Listing
    {
        $updated = DB::transaction(function () use ($user, $id, $payload) {
            $listing = Listing::query()->lockForUpdate()->findOrFail($id);

            if ((int) $listing->owner_id !== (int) $user->id) {
                throw new BusinessException(ErrorCode::AuthForbidden);
            }

            if ($listing->demand_type === 'RENT') {
                throw new BusinessException(ErrorCode::BadRequest, 'Tin cho thue khong ho tro xac thuc bat dong san.');
            }

            $identityCardFront = $payload['identity_card_front'] ?? null;
            $identityCardBack = $payload['identity_card_back'] ?? null;
            $legalDocuments = $payload['legal_documents'] ?? [];
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
                    'public_info_agreed' => (bool) ($payload['public_info_agreed'] ?? false),
                ]);
            }

            $this->listingRepository->deleteVerificationDocuments($listing->id);
            $sortOrder = 0;

            if ($identityCardFront) {
                $this->createVerificationDocument($listing, 'ID_FRONT', $identityCardFront, $sortOrder++);
            }

            if ($identityCardBack) {
                $this->createVerificationDocument($listing, 'ID_BACK', $identityCardBack, $sortOrder++);
            }

            foreach ($legalDocuments as $documentUrl) {
                $this->createVerificationDocument($listing, 'LEGAL_DOCUMENT', $documentUrl, $sortOrder++);
            }

            return $listing->fresh()->load([
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointments',
            ]);
        });

        ListingSaved::dispatch($updated, $user, 'verification_submitted');

        return $updated;
    }

    private function createVerificationDocument(Listing $listing, string $type, string $url, int $sortOrder): void
    {
        $this->listingRepository->createVerificationDocument([
            'listing_id' => $listing->id,
            'document_type' => $type,
            'file_url' => $url,
            'mime_type' => 'image/jpeg',
            'file_size' => 0,
            'sort_order' => $sortOrder,
        ]);
    }
}
