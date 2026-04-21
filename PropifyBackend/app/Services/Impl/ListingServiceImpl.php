<?php

namespace App\Services\Impl;

use App\DTOs\CreateListingDto;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\Listing;
use App\Models\User;
use App\Repositories\ListingRepository;
use App\Services\ListingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ListingServiceImpl implements ListingService
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
    ) {
    }

    public function create(User $user, CreateListingDto $dto): Listing
    {
        if (!$user->phone || trim((string) $user->phone) === '') {
            Log::warning('[ListingService] Blocked: user has no phone', ['user_id' => $user->id]);
            throw new BusinessException(ErrorCode::AuthPhoneNotVerified);
        }

        return DB::transaction(function () use ($user, $dto) {
            $property = $this->listingRepository->createProperty([
                'owner_id' => $user->id,
                'type' => $dto->propertyType,
                'province_code' => $dto->provinceCode,
                'district_code' => $dto->districtCode,
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

            if ($dto->attributeIds !== []) {
                $property->attributes()->sync($dto->attributeIds);
            }

            $listing = $this->listingRepository->createListing([
                'property_id' => $property->id,
                'demand_type' => $dto->demandType,
                'title' => $dto->title,
                'description' => $dto->description,
                'status' => 'PENDING',
                'package_id' => $dto->packageId,
                'score' => 0,
                'is_verified' => false,
                'has_video' => $dto->video !== null,
                'request_verification' => $dto->requestVerification,
                'appointment_at' => $dto->appointmentAt,
                'appointment_days' => $dto->appointmentDays,
                'appointment_time_slot' => $dto->appointmentTimeSlot,
                'appointment_contact_name' => $dto->appointmentContactName,
                'appointment_contact_phone' => $dto->appointmentContactPhone,
                'appointment_contact_email' => $dto->appointmentContactEmail,
                'appointment_note' => $dto->appointmentNote,
                'submitted_at' => now(),
            ]);

            foreach ($dto->images as $index => $imageUrl) {
                Log::debug('[ListingService] Image saved', ['index' => $index, 'url' => $imageUrl]);
                $this->listingRepository->createListingImage([
                    'listing_id' => $listing->id,
                    'image_url' => $imageUrl,
                    'is_thumbnail' => $index === 0,
                    'sort_order' => $index,
                ]);
            }

            if ($dto->video !== null) {
                Log::debug('[ListingService] Saving video', ['url' => $dto->video]);
                $this->listingRepository->createListingVideo([
                    'listing_id' => $listing->id,
                    'video_url' => $dto->video,
                    'provider' => 'CLOUDINARY',
                    'mime_type' => 'video/mp4',
                    'file_size' => 0,
                ]);
            }

            if ($dto->requestVerification) {
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

            return $listing->load([
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointments',
            ]);
        });
    }
}
