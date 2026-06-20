<?php

namespace App\Services\Appointment\Command;

use App\Enums\BookingStatus;
use App\Events\Appointment\AppointmentBookingStatusUpdated;
use App\Models\AppointmentBooking;

/**
 * Chủ nhà xác nhận lịch hẹn (PENDING → APPROVED).
 * State lo việc đổi trạng thái; command lo persist + phát event.
 */
final class ConfirmBookingCommand
{
    public function execute(AppointmentBooking $booking): void
    {
        $booking->state()->confirm($booking);
        $booking->save();

        AppointmentBookingStatusUpdated::dispatch($booking->id, BookingStatus::APPROVED->value);
    }
}
