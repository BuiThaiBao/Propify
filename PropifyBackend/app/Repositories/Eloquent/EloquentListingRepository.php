<?php

namespace App\Repositories\Eloquent;

use App\Models\Appointment;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\ListingVerificationDocument;
use App\Models\ListingVideo;
use App\Models\Property;
use App\Repositories\ListingRepository;
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
                'appointments',
                'owner',
            ])
            ->findOrFail($id);
    }

    public function paginatePublic(?string $demandType, ?string $keyword, int $perPage): LengthAwarePaginator
    {
        return Listing::query()
            ->select([
                'listings.id', 'listings.property_id', 'listings.owner_id', 'listings.title',
                'listings.demand_type', 'listings.status', 'listings.is_verified',
                'listings.has_video', 'listings.package_id', 'listings.score',
                'listings.submitted_at', 'listings.published_at',
            ])
            // Tính final_score trong SQL để sort chính xác
            ->selectRaw('
                COALESCE(packages.priority, 1) AS pkg_priority,
                (
                    COALESCE(listings.score, 0)
                    * COALESCE(packages.multiplier, 1.0)
                    * (1.0 / (1.0 + TIMESTAMPDIFF(HOUR, COALESCE(listings.published_at, listings.created_at), NOW()) / 24.0))
                    * EXP(-COALESCE(packages.decay_rate, 0.05) * TIMESTAMPDIFF(HOUR, COALESCE(listings.published_at, listings.created_at), NOW()))
                ) AS final_score
            ')
            ->leftJoin('packages', 'listings.package_id', '=', 'packages.id')
            ->with([
                'property:id,type,address_detail,area,price,bedrooms,bathrooms,contact_name,poster_type,project_name',
                'images:id,listing_id,image_url,is_thumbnail,sort_order',
                'owner:id,full_name,avatar_url',
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
            })
            // 🔥 Ranking: Sort theo tầng ưu tiên (priority) trước, rồi mới so final_score
            ->orderByDesc('pkg_priority')
            ->orderByDesc('final_score')
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
