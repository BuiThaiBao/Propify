<?php

namespace App\DTOs\Appointment;

readonly class GetAppointmentSlotsDto
{
    public function __construct(
        public int $listingId
    ) {
    }
}
