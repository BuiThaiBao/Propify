<?php

namespace App\Repositories;

use App\Models\UserFavorite;
use Illuminate\Support\Collection;

interface FavoriteRepository
{
    public function findByUser(int $userId, string $type = 'FAVORITE'): Collection;

    public function findIdsByUser(int $userId, string $type = 'FAVORITE'): Collection;

    public function findByUserAndListing(int $userId, int $listingId, string $type): ?UserFavorite;

    public function create(array $attributes): UserFavorite;

    public function delete(int $id): void;
}
