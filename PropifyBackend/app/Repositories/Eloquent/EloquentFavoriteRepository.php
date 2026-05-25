<?php

namespace App\Repositories\Eloquent;

use App\Models\UserFavorite;
use App\Repositories\FavoriteRepository;
use Illuminate\Support\Collection;

final class EloquentFavoriteRepository implements FavoriteRepository
{
    public function findByUser(int $userId, string $type = 'FAVORITE'): Collection
    {
        return UserFavorite::query()
            ->with([
                'listing.property.attributes.group',
                'listing.images',
                'listing.videos',
                'listing.verificationDocuments',
                'listing.appointmentSlots',
                'listing.appointments',
                'listing.owner',
                'listing.package',
            ])
            ->where('user_id', $userId)
            ->where('type', $type)
            ->latest()
            ->get();
    }

    public function findIdsByUser(int $userId, string $type = 'FAVORITE'): Collection
    {
        return UserFavorite::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->pluck('listing_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function findByUserAndListing(int $userId, int $listingId, string $type): ?UserFavorite
    {
        return UserFavorite::query()
            ->where('user_id', $userId)
            ->where('listing_id', $listingId)
            ->where('type', $type)
            ->first();
    }

    public function create(array $attributes): UserFavorite
    {
        return UserFavorite::query()->create($attributes);
    }

    public function delete(int $id): void
    {
        UserFavorite::query()->whereKey($id)->delete();
    }
}
