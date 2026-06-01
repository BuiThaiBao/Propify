<?php

namespace App\Repositories\Eloquent;

use App\Models\Appointment;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\ListingVerificationDocument;
use App\Models\ListingVideo;
use App\Models\Property;
use App\Repositories\ListingRepository;
use App\Services\Listing\Sorting\ListingSortingStrategy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentListingRepository implements ListingRepository
{
    public function createProperty(array $attributes): Property
    {
        return Property::create($attributes);
    }

    public function createListing(array $attributes): Listing
    {
        return Listing::create($attributes);
    }

    public function createListingImage(array $attributes): ListingImage
    {
        return ListingImage::create($attributes);
    }

    public function createListingVideo(array $attributes): ListingVideo
    {
        return ListingVideo::create($attributes);
    }

    public function createVerificationDocument(array $attributes): ListingVerificationDocument
    {
        return ListingVerificationDocument::create($attributes);
    }

    public function createAppointment(array $attributes): Appointment
    {
        return Appointment::create($attributes);
    }

    public function paginateByOwner(int $ownerId, ?string $status, ?string $demandType, ?string $keyword, int $perPage): LengthAwarePaginator
    {
        return Listing::query()
            ->with([
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointmentSlots',
                'appointments',
                'package:id,name,slug,badge,color,priority',
            ])
            ->where('owner_id', $ownerId)
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($demandType, function ($query) use ($demandType) {
                $query->where('demand_type', $demandType);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery
                        ->where('title', 'like', '%'.$keyword.'%')
                        ->orWhere('description', 'like', '%'.$keyword.'%')
                        ->orWhereHas('property', function ($propertyQuery) use ($keyword) {
                            $propertyQuery
                                ->where('address_detail', 'like', '%'.$keyword.'%')
                                ->orWhere('project_name', 'like', '%'.$keyword.'%');
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findById(int $id): Listing
    {
        return Listing::query()
            ->with([
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
            ])
            ->findOrFail($id);
    }

    public function paginatePublic(
        ListingSortingStrategy $sortingStrategy,
        ?string $demandType,
        ?string $keyword,
        int $perPage,
        ?string $posterType = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?float $minArea = null,
        ?float $maxArea = null
    ): LengthAwarePaginator {
        $hasPropertyFilters = $posterType || $minPrice !== null || $maxPrice !== null || $minArea !== null || $maxArea !== null;

        $query = Listing::query()
            ->select([
                'listings.id', 'listings.property_id', 'listings.owner_id', 'listings.title',
                'listings.demand_type', 'listings.status', 'listings.is_verified',
                'listings.has_video', 'listings.package_id', 'listings.score',
                'listings.views', 'listings.submitted_at', 'listings.published_at',
            ])
            ->with([
                'property:id,type,province_code,ward_code,street_code,project_name,address_detail,area,price,bedrooms,bathrooms,contact_name,contact_phone,contact_email,poster_type',
                'images:id,listing_id,image_url,is_thumbnail,sort_order',
                'owner:id,full_name,avatar_url,phone',
                'package:id,name,slug,badge,color,priority',
            ])
            ->where('listings.status', 'ACTIVE')
            ->when($demandType, function ($query) use ($demandType) {
                $query->where('listings.demand_type', $demandType);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery
                        ->where('listings.title', 'like', '%'.$keyword.'%')
                        ->orWhere('listings.description', 'like', '%'.$keyword.'%')
                        ->orWhereHas('property', function ($propertyQuery) use ($keyword) {
                            $propertyQuery
                                ->where('address_detail', 'like', '%'.$keyword.'%')
                                ->orWhere('project_name', 'like', '%'.$keyword.'%');
                        });
                });
            })
            ->when($hasPropertyFilters, function ($query) use ($posterType, $minPrice, $maxPrice, $minArea, $maxArea) {
                $query->whereHas('property', function ($q) use ($posterType, $minPrice, $maxPrice, $minArea, $maxArea) {
                    if ($posterType) {
                        $q->where('poster_type', strtoupper($posterType));
                    }
                    if ($minPrice !== null) {
                        $q->where('price', '>=', $minPrice);
                    }
                    if ($maxPrice !== null) {
                        $q->where('price', '<=', $maxPrice);
                    }
                    if ($minArea !== null) {
                        $q->where('area', '>=', $minArea);
                    }
                    if ($maxArea !== null) {
                        $q->where('area', '<=', $maxArea);
                    }
                });
            });

        $query = $sortingStrategy->apply($query);

        return $query->paginate($perPage);
    }

    public function paginateAdmin(?string $status, ?string $demandType, ?string $keyword, int $perPage): LengthAwarePaginator
    {
        return Listing::query()
            ->with([
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointmentSlots',
                'appointments',
                'owner',
                'approver',
                'package',
            ])
            ->where('status', '!=', 'DRAFT')
            ->when($status && $status !== 'all', function ($query) use ($status) {
                // If filtering by specific status, do exact match except for 'all'
                $query->where('status', strtoupper($status));
            })
            ->when($demandType && $demandType !== 'all', function ($query) use ($demandType) {
                $query->where('demand_type', strtoupper($demandType));
            })
            ->when($keyword, function ($query) use ($keyword) {
                $keywordLower = mb_strtolower($keyword, 'UTF-8');
                $isRent = str_contains($keywordLower, 'cho') || str_contains($keywordLower, 'thuê');
                $isSale = str_contains($keywordLower, 'mua') || str_contains($keywordLower, 'bán');

                $query->where(function ($subQuery) use ($keyword, $isRent, $isSale) {
                    $subQuery
                        ->where('title', 'like', '%'.$keyword.'%')
                        ->orWhereHas('property', function ($propertyQuery) use ($keyword) {
                            $propertyQuery
                                ->where('address_detail', 'like', '%'.$keyword.'%')
                                ->orWhere('project_name', 'like', '%'.$keyword.'%');
                        });

                    if ($isRent) {
                        $subQuery->orWhere('demand_type', 'RENT');
                    }
                    if ($isSale) {
                        $subQuery->orWhere('demand_type', 'SALE');
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function updateProperty(int $id, array $attributes): Property
    {
        $property = Property::findOrFail($id);
        $property->update($attributes);

        return $property->fresh();
    }

    public function updateListing(int $id, array $attributes): Listing
    {
        $listing = Listing::findOrFail($id);
        $listing->update($attributes);

        return $listing->fresh();
    }

    public function deleteListingImages(int $listingId): void
    {
        ListingImage::where('listing_id', $listingId)->delete();
    }

    public function deleteListingVideos(int $listingId): void
    {
        ListingVideo::where('listing_id', $listingId)->delete();
    }

    public function deleteVerificationDocuments(int $listingId): void
    {
        ListingVerificationDocument::where('listing_id', $listingId)->delete();
    }
}
