<?php

namespace App\Services\Listing\Favorite;

use Illuminate\Support\Collection;

interface FavoriteService
{
    public function getUserFavorites(int $userId): Collection;

    public function getUserFavoriteIds(int $userId): Collection;

    public function toggle(int $userId, int $listingId): bool;
}
