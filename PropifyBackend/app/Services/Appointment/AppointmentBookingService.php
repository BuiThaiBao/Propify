<?php

namespace App\Services\Appointment;

use App\DTOs\Appointment\CreateBookingDto;
use App\Models\AppointmentBooking;

interface AppointmentBookingService
{
    /**
     * Tạo mới một booking lịch hẹn xem nhà.
     *
     * @throws \App\Exceptions\AppointmentSlotNotFoundException
     * @throws \App\Exceptions\BookingSelfSlotException
     * @throws \App\Exceptions\BookingInvalidDateException
     */
    public function createBooking(CreateBookingDto $dto): AppointmentBooking;
}
