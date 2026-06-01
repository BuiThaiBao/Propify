<?php

namespace App\Services\Listing\Commands;

use App\DTOs\Listing\CreateListingDto;
use App\Enums\ErrorCode;
use App\Events\Listing\ListingSaved;
use App\Exceptions\BusinessException;
use App\Models\Listing;
use App\Models\User;
use App\Repositories\ListingRepository;
use App\Services\Listing\State\ListingStatusStateFactory;
use Illuminate\Support\Facades\DB;

final class UpdateListingCommand
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly ListingStatusStateFactory $statusStateFactory,
    ) {}

    public function handle(User $user, int $id, CreateListingDto $dto): Listing
    {
        $listing = $this->listingRepository->findById($id);

        if ((int) $listing->owner_id !== (int) $user->id) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        $updated = DB::transaction(function () use ($listing, $dto) {
            $this->listingRepository->updateProperty($listing->property_id, $this->propertyAttributes($dto));

            if ($dto->attributeIds !== []) {
                $listing->property->attributes()->sync($dto->attributeIds);
            }

            $listingStatus = $this->statusStateFactory->initialForSave($dto->saveAsDraft)->value();

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

            $this->replaceImages($listing, $dto->images);
            $this->replaceVideo($listing, $dto->video);
            $this->replaceVerificationDocuments($listing, $dto);

            return $listing->fresh()->load([
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointments',
            ]);
        });

        ListingSaved::dispatch($updated, $user, $dto->saveAsDraft ? 'draft_updated' : 'updated');

        return $updated;
    }

    private function propertyAttributes(CreateListingDto $dto): array
    {
        return [
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
        ];
    }

    private function replaceImages(Listing $listing, array $images): void
    {
        $this->listingRepository->deleteListingImages($listing->id);

        foreach ($images as $index => $imageUrl) {
            $this->listingRepository->createListingImage([
                'listing_id' => $listing->id,
                'image_url' => $imageUrl,
                'is_thumbnail' => $index === 0,
                'sort_order' => $index,
            ]);
        }
    }

    private function replaceVideo(Listing $listing, ?string $videoUrl): void
    {
        $this->listingRepository->deleteListingVideos($listing->id);

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

    private function replaceVerificationDocuments(Listing $listing, CreateListingDto $dto): void
    {
        if (! $dto->requestVerification) {
            return;
        }

        $this->listingRepository->deleteVerificationDocuments($listing->id);
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
}
