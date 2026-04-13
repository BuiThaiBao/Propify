<?php

namespace App\Services\Impl;

use App\DTOs\CreateListingDto;
use App\Exceptions\PhoneNotVerifiedException;
use App\Models\Listing;
use App\Models\User;
use App\Repositories\ListingRepository;
use App\Services\ListingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ListingServiceImpl implements ListingService
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
    ) {
    }

    public function create(User $user, CreateListingDto $dto): Listing
    {
        if (!$user->phone_verified_at) {
            throw new PhoneNotVerifiedException();
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
                'submitted_at' => now(),
            ]);

            foreach ($dto->images as $index => $image) {
                $path = $image->store('listings/images', 'public');

                $this->listingRepository->createListingImage([
                    'listing_id' => $listing->id,
                    'image_url' => Storage::disk('public')->url($path),
                    'is_thumbnail' => $index === 0,
                    'sort_order' => $index,
                ]);
            }

            if ($dto->video !== null) {
                $videoPath = $dto->video->store('listings/videos', 'public');

                $this->listingRepository->createListingVideo([
                    'listing_id' => $listing->id,
                    'video_url' => Storage::disk('public')->url($videoPath),
                    'provider' => 'LOCAL',
                    'mime_type' => $dto->video->getMimeType(),
                    'file_size' => $dto->video->getSize(),
                ]);
            }

            if ($dto->requestVerification) {
                $sortOrder = 0;

                if ($dto->identityCardFront !== null) {
                    $frontPath = $dto->identityCardFront->store('listings/verification', 'public');
                    $this->listingRepository->createVerificationDocument([
                        'listing_id' => $listing->id,
                        'document_type' => 'ID_FRONT',
                        'file_url' => Storage::disk('public')->url($frontPath),
                        'mime_type' => $dto->identityCardFront->getMimeType(),
                        'file_size' => $dto->identityCardFront->getSize(),
                        'sort_order' => $sortOrder++,
                    ]);
                }

                if ($dto->identityCardBack !== null) {
                    $backPath = $dto->identityCardBack->store('listings/verification', 'public');
                    $this->listingRepository->createVerificationDocument([
                        'listing_id' => $listing->id,
                        'document_type' => 'ID_BACK',
                        'file_url' => Storage::disk('public')->url($backPath),
                        'mime_type' => $dto->identityCardBack->getMimeType(),
                        'file_size' => $dto->identityCardBack->getSize(),
                        'sort_order' => $sortOrder++,
                    ]);
                }

                foreach ($dto->legalDocuments as $document) {
                    $documentPath = $document->store('listings/verification', 'public');
                    $this->listingRepository->createVerificationDocument([
                        'listing_id' => $listing->id,
                        'document_type' => 'LEGAL_DOCUMENT',
                        'file_url' => Storage::disk('public')->url($documentPath),
                        'mime_type' => $document->getMimeType(),
                        'file_size' => $document->getSize(),
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            if ($dto->appointmentAt !== null) {
                $this->listingRepository->createAppointment([
                    'listing_id' => $listing->id,
                    'viewer_id' => null,
                    'poster_id' => $user->id,
                    'meet_time' => $dto->appointmentAt,
                    'contact_name' => $dto->appointmentContactName ?? $dto->contactName,
                    'contact_phone' => $dto->appointmentContactPhone ?? $dto->contactPhone,
                    'contact_email' => $dto->appointmentContactEmail ?? $dto->contactEmail,
                    'note' => $dto->appointmentNote,
                    'status' => 'PENDING',
                ]);
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
