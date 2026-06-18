<?php

namespace App\Repositories;

use App\Models\Appointment;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\ListingVerificationDocument;
use App\Models\ListingVideo;
use App\Models\Property;
use App\Services\Listing\Sorting\ListingSortingStrategy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ListingRepository
{
    public function createProperty(array $attributes): Property;

    public function createListing(array $attributes): Listing;

    public function createListingImage(array $attributes): ListingImage;

    public function createListingVideo(array $attributes): ListingVideo;

    public function createVerificationDocument(array $attributes): ListingVerificationDocument;

    public function createAppointment(array $attributes): Appointment;

    public function paginateByOwner(int $ownerId, ?string $status, ?string $demandType, ?string $keyword, int $perPage): LengthAwarePaginator;

    public function findById(int $id): Listing;

    public function paginatePublic(
        ListingSortingStrategy $sortingStrategy,
        ?string $demandType,
        ?string $keyword,
        int $perPage,
        ?string $searchField = null,
        ?string $posterType = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?float $minArea = null,
        ?float $maxArea = null,
        ?string $propertyType = null
    ): LengthAwarePaginator;

    public function getMapListings(
        ?string $demandType,
        ?string $keyword,
        ?string $searchField = null,
        ?string $posterType = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?float $minArea = null,
        ?float $maxArea = null,
        ?string $propertyType = null
    ): Collection;

    public function paginateAdmin(
        ?string $status,
        ?string $demandType,
        ?string $keyword,
        int $perPage,
        ?string $searchField = 'title',
        ?string $priceRange = null,
        ?int $packageId = null,
    ): LengthAwarePaginator;

    /**
     * @return array<string, int>
     */
    public function getAdminStatusCounts(
        ?string $demandType,
        ?string $keyword,
        ?string $searchField = 'title',
        ?string $priceRange = null,
        ?int $packageId = null,
    ): array;

    public function updateProperty(int $id, array $attributes): Property;

    public function updateListing(int $id, array $attributes): Listing;

    public function deleteListingImages(int $listingId): void;

    public function deleteListingVideos(int $listingId): void;

    public function deleteVerificationDocuments(int $listingId): void;
}
