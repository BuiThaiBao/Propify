<?php

namespace App\Repositories\Eloquent;

use App\Models\Listing;
use App\Repositories\ListingAmenityRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentListingAmenityRepository implements ListingAmenityRepository
{
    public function findListingWithProperty(int $listingId): ?Listing
    {
        return Listing::query()
            ->with('property')
            ->find($listingId);
    }

    public function getListingAmenities(Listing $listing): Collection
    {
        return $listing->property
            ->amenities()
            ->with('group')
            ->orderBy('property_attributes.display_order')
            ->orderBy('attributes.order_index')
            ->orderBy('attributes.name')
            ->get();
    }

    public function syncListingAmenities(Listing $listing, array $amenities): Collection
    {
        DB::transaction(function () use ($listing, $amenities): void {
            $propertyId = (int) $listing->property_id;
            $amenityIds = collect($amenities)->pluck('amenity_id')->map(fn ($id) => (int) $id)->all();

            DB::table('property_attributes')
                ->where('property_id', $propertyId)
                ->whereIn('attribute_id', function ($query): void {
                    $query->select('attributes.id')
                        ->from('attributes')
                        ->join('attribute_groups', 'attribute_groups.id', '=', 'attributes.group_id')
                        ->where('attribute_groups.code', 'amenities');
                })
                ->when($amenityIds !== [], fn ($query) => $query->whereNotIn('attribute_id', $amenityIds))
                ->delete();

            foreach ($amenities as $amenity) {
                DB::table('property_attributes')->updateOrInsert(
                    [
                        'property_id' => $propertyId,
                        'attribute_id' => (int) $amenity['amenity_id'],
                    ],
                    [
                        'is_visible' => (bool) $amenity['is_visible'],
                        'display_order' => (int) $amenity['display_order'],
                        'is_featured' => (bool) $amenity['is_featured'],
                    ],
                );
            }
        });

        return $this->getListingAmenities($listing->fresh('property'));
    }
}
