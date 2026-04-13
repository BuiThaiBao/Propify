<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;

interface AppointmentSlotRepository
{
    /**
     * Get all active appointment slots by listing ID.
     *
     * @return Collection<int, \App\Models\AppointmentSlot>
     */
    public function getByListingId(int $listingId): Collection;

    /**
     * Get all active appointment slots by poster ID.
     *
     * @return Collection<int, \App\Models\AppointmentSlot>
     */
    public function getByPosterId(int $posterId): Collection;

    /**
     * Get all active appointment slots by listing ID and poster ID.
     *
     * @return Collection<int, \App\Models\AppointmentSlot>
     */
    public function getByListingAndPoster(int $listingId, int $posterId): Collection;
}
