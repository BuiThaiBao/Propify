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
                        ->where('title', 'like', '%' . $keyword . '%')
                        ->orWhere('description', 'like', '%' . $keyword . '%')
                        ->orWhereHas('property', function ($propertyQuery) use ($keyword) {
                            $propertyQuery
                                ->where('address_detail', 'like', '%' . $keyword . '%')
                                ->orWhere('project_name', 'like', '%' . $keyword . '%');
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

    public function paginatePublic(ListingSortingStrategy $sortingStrategy, ?string $demandType, ?string $keyword, int $perPage): LengthAwarePaginator
    {
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
                        ->where('listings.title', 'like', '%' . $keyword . '%')
                        ->orWhere('listings.description', 'like', '%' . $keyword . '%')
                        ->orWhereHas('property', function ($propertyQuery) use ($keyword) {
                            $propertyQuery
                                ->where('address_detail', 'like', '%' . $keyword . '%')
                                ->orWhere('project_name', 'like', '%' . $keyword . '%');
                        });
                });
            });

        $query = $sortingStrategy->apply($query);

        return $query->paginate($perPage);
    }

    public function paginateAdmin(
        ?string $status,
        ?string $demandType,
        ?string $keyword,
        int $perPage,
        ?string $searchField = 'title',
        ?string $priceRange = null,
        ?int $packageId = null,
    ): LengthAwarePaginator
    {
        return $this->buildAdminBaseQuery($demandType, $keyword, $searchField, $priceRange, $packageId)
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
            ->when($status && $status !== 'all', function ($query) use ($status) {
                // If filtering by specific status, do exact match except for 'all'
                $query->where('status', strtoupper($status));
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getAdminStatusCounts(
        ?string $demandType,
        ?string $keyword,
        ?string $searchField = 'title',
        ?string $priceRange = null,
        ?int $packageId = null,
    ): array {
        $counts = $this->buildAdminBaseQuery($demandType, $keyword, $searchField, $priceRange, $packageId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'all' => (int) $counts->sum(),
            'pending' => (int) ($counts['PENDING'] ?? 0),
            'approved' => (int) ($counts['ACTIVE'] ?? 0),
            'rejected' => (int) ($counts['REJECTED'] ?? 0),
            'locked' => (int) ($counts['LOCKED'] ?? 0),
        ];
    }

    private function buildAdminBaseQuery(
        ?string $demandType,
        ?string $keyword,
        ?string $searchField = 'title',
        ?string $priceRange = null,
        ?int $packageId = null,
    ) {
        return Listing::query()
            ->whereNotIn('status', ['DRAFT', 'UNLISTED'])
            ->when($demandType && $demandType !== 'all', function ($query) use ($demandType) {
                $query->where('demand_type', strtoupper($demandType));
            })
            ->when($packageId, function ($query) use ($packageId) {
                $query->where('package_id', $packageId);
            })
            ->when($priceRange && $priceRange !== 'all', function ($query) use ($priceRange) {
                $query->whereHas('property', function ($propertyQuery) use ($priceRange) {
                    match ($priceRange) {
                        'under_1b' => $propertyQuery->where('price', '<', 1000000000),
                        '1b_3b' => $propertyQuery->whereBetween('price', [1000000000, 3000000000]),
                        '3b_5b' => $propertyQuery->whereBetween('price', [3000000000, 5000000000]),
                        '5b_10b' => $propertyQuery->whereBetween('price', [5000000000, 10000000000]),
                        'over_10b' => $propertyQuery->where('price', '>', 10000000000),
                        default => null,
                    };
                });
            })
            ->when($keyword, function ($query) use ($keyword, $searchField) {
                $keywordLower = mb_strtolower($keyword, 'UTF-8');
                $isRent = str_contains($keywordLower, 'cho') || str_contains($keywordLower, 'thuê');
                $isSale = str_contains($keywordLower, 'mua') || str_contains($keywordLower, 'bán');

                $query->where(function ($subQuery) use ($keyword, $isRent, $isSale, $searchField) {
                    if ($searchField === 'owner') {
                        $subQuery->whereHas('owner', function ($ownerQuery) use ($keyword) {
                            $ownerQuery
                                ->where('full_name', 'like', '%' . $keyword . '%')
                                ->orWhere('email', 'like', '%' . $keyword . '%')
                                ->orWhere('phone', 'like', '%' . $keyword . '%');
                        });
                    } elseif ($searchField === 'address') {
                        $subQuery->whereHas('property', function ($propertyQuery) use ($keyword) {
                            $propertyQuery
                                ->where('address_detail', 'like', '%' . $keyword . '%')
                                ->orWhere('project_name', 'like', '%' . $keyword . '%')
                                ->orWhere('street_code', 'like', '%' . $keyword . '%')
                                ->orWhere('province_code', 'like', '%' . $keyword . '%')
                                ->orWhere('ward_code', 'like', '%' . $keyword . '%');
                        });
                    } else {
                        $subQuery->where('title', 'like', '%' . $keyword . '%');

                        if ($isRent) {
                            $subQuery->orWhere('demand_type', 'RENT');
                        }
                        if ($isSale) {
                            $subQuery->orWhere('demand_type', 'SALE');
                        }
                    }
                });
            });
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
