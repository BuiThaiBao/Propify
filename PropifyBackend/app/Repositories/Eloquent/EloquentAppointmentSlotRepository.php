<?php

namespace App\Repositories\Eloquent;

use App\Models\AppointmentSlot;
use App\Repositories\AppointmentSlotRepository;
use Illuminate\Database\Eloquent\Collection;

final class EloquentAppointmentSlotRepository implements AppointmentSlotRepository
{
    public function __construct(
        protected readonly AppointmentSlot $model
    ) {
    }

    public function getByListingId(int $listingId): Collection
    {
        return $this->model
            ->where('listing_id', $listingId)
            ->where('is_active', true)
            ->with(['listing.property', 'poster'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    public function getByPosterId(int $posterId): Collection
    {
        return $this->model
            ->where('poster_id', $posterId)
            ->where('is_active', true)
            ->with(['listing.property', 'poster'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    public function getByListingAndPoster(int $listingId, int $posterId): Collection
    {
        return $this->model
            ->where('listing_id', $listingId)
            ->where('poster_id', $posterId)
            ->where('is_active', true)
            ->with(['listing.property', 'poster'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }
}
