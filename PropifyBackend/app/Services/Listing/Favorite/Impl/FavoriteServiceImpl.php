<?php

namespace App\Services\Listing\Favorite\Impl;

use App\Events\Listing\FavoriteToggled;
use App\Models\Listing;
use App\Repositories\FavoriteRepository;
use App\Services\Listing\Favorite\FavoriteService;
use Illuminate\Support\Collection;

final class FavoriteServiceImpl implements FavoriteService
{
    public function __construct(
        private readonly FavoriteRepository $favoriteRepository
    ) {}

    public function getUserFavorites(int $userId): Collection
    {
        return $this->favoriteRepository->findByUser($userId)
            ->pluck('listing')
            ->filter()
            ->values()
            ->each(fn (Listing $listing) => $listing->setAttribute('is_favorited', true));
    }

    public function getUserFavoriteIds(int $userId): Collection
    {
        return $this->favoriteRepository->findIdsByUser($userId);
    }

    public function toggle(int $userId, int $listingId): bool
    {
        Listing::query()->findOrFail($listingId);

        $favorite = $this->favoriteRepository->findByUserAndListing($userId, $listingId, 'FAVORITE');

        if ($favorite !== null) {
            $this->favoriteRepository->delete($favorite->id);
            event(new FavoriteToggled($userId, $listingId, false));

            return false;
        }

        $this->favoriteRepository->create([
            'user_id' => $userId,
            'listing_id' => $listingId,
            'type' => 'FAVORITE',
        ]);

        event(new FavoriteToggled($userId, $listingId, true));

        return true;
    }

    public function getUserRecentlyViewed(int $userId): Collection
    {
        return $this->favoriteRepository->findByUser($userId, 'VIEWED')
            ->pluck('listing')
            ->filter()
            ->values();
    }

    public function trackView(int $userId, int $listingId): void
    {
        Listing::query()->findOrFail($listingId);

        $viewed = $this->favoriteRepository->findByUserAndListing($userId, $listingId, 'VIEWED');

        if ($viewed !== null) {
            $viewed->touch();

            return;
        }

        $this->favoriteRepository->create([
            'user_id' => $userId,
            'listing_id' => $listingId,
            'type' => 'VIEWED',
        ]);
    }
}
