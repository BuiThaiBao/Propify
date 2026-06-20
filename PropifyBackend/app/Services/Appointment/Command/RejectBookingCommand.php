<?php

namespace App\Services\Appointment\Command;

use App\Enums\BookingStatus;
use App\Events\Appointment\AppointmentBookingStatusUpdated;
use App\Models\AppointmentBooking;

/**
 * Chủ nhà từ chối lịch hẹn (PENDING → CANCELLED_BY_POSTER), ghi lý do vào note.
 */
final class RejectBookingCommand
{
    public function execute(AppointmentBooking $booking, ?string $note): void
    {
        $booking->state()->reject($booking, $note);
        $booking->save();

        AppointmentBookingStatusUpdated::dispatch($booking->id, BookingStatus::CANCELLED_BY_POSTER->value);
    }
}
