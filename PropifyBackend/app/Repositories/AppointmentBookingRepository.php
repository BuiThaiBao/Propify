<?php

namespace App\Repositories;

use App\Models\AppointmentBooking;

interface AppointmentBookingRepository
{
    /**
     * Tạo mới một booking.
     */
    public function create(array $data): AppointmentBooking;
}
