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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
                $normalizedKeyword = $this->normalizeSearchKeyword($keyword);

                if ($normalizedKeyword === null) {
                    return;
                }

                $like = '%'.$normalizedKeyword.'%';

                $query->where(function ($subQuery) use ($like) {
                    $subQuery
                        ->whereRaw($this->normalizeSearchExpression('title').' LIKE ?', [$like])
                        ->orWhereRaw($this->normalizeSearchExpression('description').' LIKE ?', [$like])
                        ->orWhereHas('property', function ($propertyQuery) use ($like) {
                            $propertyQuery
                                ->whereRaw($this->normalizeSearchExpression('address_detail').' LIKE ?', [$like])
                                ->orWhereRaw($this->normalizeSearchExpression('project_name').' LIKE ?', [$like])
                                ->orWhereRaw($this->normalizeSearchExpression('province').' LIKE ?', [$like])
                                ->orWhereRaw($this->normalizeSearchExpression('ward').' LIKE ?', [$like]);
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
                'property:id,type,province_code,province,ward_code,ward,street_code,project_name,address_detail,area,price,bedrooms,bathrooms,contact_name,contact_phone,contact_email,poster_type,lat,lng',
                'images:id,listing_id,image_url,is_thumbnail,sort_order',
                'owner:id,full_name,avatar_url,phone',
                'package:id,name,slug,badge,color,priority',
            ])
            ->where('listings.status', 'ACTIVE')
            ->when($demandType, function ($query) use ($demandType) {
                $query->where('listings.demand_type', $demandType);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $normalizedKeyword = $this->normalizeSearchKeyword($keyword);

                if ($normalizedKeyword === null) {
                    return;
                }

                $like = '%'.$normalizedKeyword.'%';

                $query->where(function ($subQuery) use ($like) {
                    $subQuery
                        ->whereRaw($this->normalizeSearchExpression('listings.title').' LIKE ?', [$like])
                        ->orWhereRaw($this->normalizeSearchExpression('listings.description').' LIKE ?', [$like])
                        ->orWhereHas('property', function ($propertyQuery) use ($like) {
                            $propertyQuery
                                ->whereRaw($this->normalizeSearchExpression('address_detail').' LIKE ?', [$like])
                                ->orWhereRaw($this->normalizeSearchExpression('project_name').' LIKE ?', [$like])
                                ->orWhereRaw($this->normalizeSearchExpression('province').' LIKE ?', [$like])
                                ->orWhereRaw($this->normalizeSearchExpression('ward').' LIKE ?', [$like]);
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
                $normalizedKeyword = $this->normalizeSearchKeyword($keyword);

                if ($normalizedKeyword === null) {
                    return;
                }

                $isRent = str_contains($normalizedKeyword, 'cho') || str_contains($normalizedKeyword, 'thue');
                $isSale = str_contains($normalizedKeyword, 'mua') || str_contains($normalizedKeyword, 'ban');
                $like = '%'.$normalizedKeyword.'%';

                $query->where(function ($subQuery) use ($like, $isRent, $isSale) {
                    $subQuery
                        ->whereRaw($this->normalizeSearchExpression('title').' LIKE ?', [$like])
                        ->orWhereHas('property', function ($propertyQuery) use ($like) {
                            $propertyQuery
                                ->whereRaw($this->normalizeSearchExpression('address_detail').' LIKE ?', [$like])
                                ->orWhereRaw($this->normalizeSearchExpression('project_name').' LIKE ?', [$like])
                                ->orWhereRaw($this->normalizeSearchExpression('province').' LIKE ?', [$like])
                                ->orWhereRaw($this->normalizeSearchExpression('ward').' LIKE ?', [$like]);
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

    public function getMapListings(
        ?string $demandType,
        ?string $keyword,
        ?string $posterType = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?float $minArea = null,
        ?float $maxArea = null
    ): Collection {
        $normalizedKeyword = $this->normalizeSearchKeyword($keyword);
        $like = $normalizedKeyword !== null ? '%'.$normalizedKeyword.'%' : null;

        return Listing::query()
            ->select(['id', 'property_id', 'title', 'demand_type'])
            ->with([
                'property:id,address_detail,price,area,poster_type,lat,lng,project_name,province,ward',
                'images:id,listing_id,image_url,is_thumbnail',
            ])
            ->where('status', 'ACTIVE')
            ->when($demandType, fn ($query) => $query->where('demand_type', $demandType))
            ->when($like, function ($query) use ($like) {
                $query->where(function ($subQuery) use ($like) {
                    $subQuery
                        ->whereRaw($this->normalizeSearchExpression('title').' LIKE ?', [$like])
                        ->orWhereHas('property', fn ($propertyQuery) => $propertyQuery
                            ->whereRaw($this->normalizeSearchExpression('address_detail').' LIKE ?', [$like])
                            ->orWhereRaw($this->normalizeSearchExpression('project_name').' LIKE ?', [$like])
                            ->orWhereRaw($this->normalizeSearchExpression('province').' LIKE ?', [$like])
                            ->orWhereRaw($this->normalizeSearchExpression('ward').' LIKE ?', [$like]));
                });
            })
            ->whereHas('property', function ($query) use ($posterType, $minPrice, $maxPrice, $minArea, $maxArea) {
                $query->whereNotNull('lat')
                    ->whereNotNull('lng');

                if ($posterType) {
                    $query->where('poster_type', strtoupper($posterType));
                }
                if ($minPrice !== null) {
                    $query->where('price', '>=', $minPrice);
                }
                if ($maxPrice !== null) {
                    $query->where('price', '<=', $maxPrice);
                }
                if ($minArea !== null) {
                    $query->where('area', '>=', $minArea);
                }
                if ($maxArea !== null) {
                    $query->where('area', '<=', $maxArea);
                }
            })
            ->orderByDesc('id')
            ->get();
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

    private function normalizeSearchKeyword(?string $keyword): ?string
    {
        $normalized = trim((string) $keyword);

        if ($normalized === '') {
            return null;
        }

        $normalized = Str::ascii(mb_strtolower($normalized, 'UTF-8'));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?: $normalized;

        return $normalized;
    }

    private function normalizeSearchExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "normalize_text($column)",
            'mysql', 'mariadb' => "LOWER(CONVERT($column USING utf8mb4)) COLLATE utf8mb4_unicode_ci",
            default => "LOWER($column)",
        };
    }
}
