<?php

namespace App\Services\Listing\Commands;

use App\DTOs\Listing\CreateListingDto;
use App\Events\Listing\ListingSaved;
use App\Models\Listing;
use App\Models\User;
use App\Repositories\ListingRepository;
use App\Services\Listing\State\ListingStatusStateFactory;
use App\Services\Listing\Validation\ListingSubmissionValidationContext;
use App\Services\Listing\Validation\ListingSubmissionValidationPipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CreateListingCommand
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly ListingStatusStateFactory $statusStateFactory,
        private readonly ListingSubmissionValidationPipeline $validationPipeline,
    ) {}

    public function handle(User $user, CreateListingDto $dto): Listing
    {
        Log::debug('[CreateListingCommand] handle() called', [
            'user_id' => $user->id,
            'demand_type' => $dto->demandType,
            'title' => $dto->title,
            'images_count' => count($dto->images),
            'has_video' => $dto->video !== null,
            'request_verification' => $dto->requestVerification,
        ]);

        $this->validationPipeline->validate(new ListingSubmissionValidationContext($user, $dto));

        $listing = DB::transaction(function () use ($user, $dto) {
            $property = $this->listingRepository->createProperty($this->propertyAttributes($dto));

            if ($dto->attributeIds !== []) {
                $property->attributes()->sync($dto->attributeIds);
            }

            $listingStatus = $this->statusStateFactory->initialForSave($dto->saveAsDraft)->value();

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

            $this->saveImages($listing, $dto->images);
            $this->saveVideo($listing, $dto->video);
            $this->saveVerificationDocuments($listing, $dto);

            return $listing->load([
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointments',
            ]);
        });

        ListingSaved::dispatch($listing, $user, $dto->saveAsDraft ? 'draft_created' : 'created');

        return $listing;
    }

    private function propertyAttributes(CreateListingDto $dto): array
    {
        return [
            'type' => $dto->propertyType,
            'province_code' => $dto->provinceCode,
            'province' => $dto->province,
            'ward_code' => $dto->wardCode,
            'ward' => $dto->ward,
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
        ];
    }

    private function saveImages(Listing $listing, array $images): void
    {
        foreach ($images as $index => $imageUrl) {
            $this->listingRepository->createListingImage([
                'listing_id' => $listing->id,
                'image_url' => $imageUrl,
                'is_thumbnail' => $index === 0,
                'sort_order' => $index,
            ]);
        }
    }

    private function saveVideo(Listing $listing, ?string $videoUrl): void
    {
        if ($videoUrl === null) {
            return;
        }

        $this->listingRepository->createListingVideo([
            'listing_id' => $listing->id,
            'video_url' => $videoUrl,
            'provider' => 'CLOUDINARY',
            'mime_type' => 'video/mp4',
            'file_size' => 0,
        ]);
    }

    private function saveVerificationDocuments(Listing $listing, CreateListingDto $dto): void
    {
        if (! $dto->requestVerification) {
            return;
        }

        $sortOrder = 0;

        if ($dto->identityCardFront !== null) {
            $this->createVerificationDocument($listing, 'ID_FRONT', $dto->identityCardFront, $sortOrder++);
        }

        if ($dto->identityCardBack !== null) {
            $this->createVerificationDocument($listing, 'ID_BACK', $dto->identityCardBack, $sortOrder++);
        }

        foreach ($dto->legalDocuments as $documentUrl) {
            $this->createVerificationDocument($listing, 'LEGAL_DOCUMENT', $documentUrl, $sortOrder++);
        }
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

    private function calculateContentScore(CreateListingDto $dto): int
    {
        $score = 0;

        if (! empty($dto->title) && mb_strlen($dto->title) >= 10) {
            $score += 10;
        }

        if (! empty($dto->description) && mb_strlen($dto->description) >= 20) {
            $score += 10;
        }

        if (! empty($dto->images) && count($dto->images) > 0) {
            $score += 20;
        }

        if ($dto->video !== null) {
            $score += 10;
        }

        $hasFullInfo = ($dto->price > 0)
            && ($dto->area > 0)
            && (! empty($dto->addressDetail) || ! empty($dto->projectName));

        if ($hasFullInfo) {
            $score += 20;
        }

        if ($dto->requestVerification) {
            $score += 5;
        }

        return min($score, 100);
    }
}
