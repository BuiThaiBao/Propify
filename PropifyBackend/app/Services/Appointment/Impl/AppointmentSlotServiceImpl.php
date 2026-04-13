<?php

namespace App\Services\Appointment\Impl;

use App\Exceptions\AppointmentSlotNotFoundException;
use App\Repositories\AppointmentSlotRepository;
use App\Services\Appointment\AppointmentSlotService;
use Illuminate\Database\Eloquent\Collection;

final class AppointmentSlotServiceImpl implements AppointmentSlotService
{
    public function __construct(
        private readonly AppointmentSlotRepository $appointmentSlotRepository
    ) {
    }

    public function getSlotsByListingAndPoster(int $listingId, int $posterId): Collection
    {
        $slots = $this->appointmentSlotRepository->getByListingAndPoster($listingId, $posterId);

        if ($slots->isEmpty()) {
            throw new AppointmentSlotNotFoundException();
        }

        return $slots;
    }
}

