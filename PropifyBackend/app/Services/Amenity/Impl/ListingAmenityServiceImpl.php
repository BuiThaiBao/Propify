<?php

namespace App\Services\Amenity\Impl;

use App\DTOs\Amenity\UpdateListingAmenitiesDto;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\Listing;
use App\Models\User;
use App\Repositories\ListingAmenityRepository;
use App\Services\Amenity\ListingAmenityService;
use Illuminate\Database\Eloquent\Collection;

final class ListingAmenityServiceImpl implements ListingAmenityService
{
    public function __construct(
        private readonly ListingAmenityRepository $listingAmenityRepository,
    ) {}

    public function getByListing(User $user, int $listingId): Collection
    {
        $listing = $this->getOwnedListing($user, $listingId);

        return $this->listingAmenityRepository->getListingAmenities($listing);
    }

    public function updateForListing(User $user, int $listingId, UpdateListingAmenitiesDto $dto): Collection
    {
        $listing = $this->getOwnedListing($user, $listingId);

        return $this->listingAmenityRepository->syncListingAmenities($listing, $dto->amenities);
    }

    private function getOwnedListing(User $user, int $listingId): Listing
    {
        $listing = $this->listingAmenityRepository->findListingWithProperty($listingId);

        if (! $listing) {
            throw new BusinessException(ErrorCode::ListingNotFound);
        }

        if ((int) $listing->owner_id !== (int) $user->id) {
            throw new BusinessException(ErrorCode::ListingNotOwned);
        }

        if (! $listing->property) {
            throw new BusinessException(ErrorCode::ResourceNotFound);
        }

        return $listing;
    }
}
