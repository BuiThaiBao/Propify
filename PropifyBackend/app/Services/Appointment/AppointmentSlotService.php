<?php

namespace App\Services\Appointment;

use Illuminate\Database\Eloquent\Collection;

interface AppointmentSlotService
{
    /**
     * Get all active appointment slots for a listing uploaded by a specific poster.
     *
     * @return Collection<int, \App\Models\AppointmentSlot>
     */
    public function getSlotsByListingAndPoster(int $listingId, int $posterId): Collection;
}
