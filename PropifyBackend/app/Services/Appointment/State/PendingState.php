<?php

namespace App\Services\Appointment\State;

use App\Enums\BookingStatus;
use App\Models\AppointmentBooking;

/**
 * PENDING: chờ chủ nhà xử lý. Cho phép confirm / reject / cancel.
 */
final class PendingState extends AbstractBookingState
{
    public function confirm(AppointmentBooking $booking): void
    {
        $booking->status = BookingStatus::APPROVED->value;
    }

    public function reject(AppointmentBooking $booking, ?string $note): void
    {
        $booking->status = BookingStatus::CANCELLED_BY_POSTER->value;

        if ($note) {
            $this->appendNote($booking, 'Chủ nhà từ chối', $note);
        }
    }

    public function cancel(AppointmentBooking $booking, BookingRole $by, string $reason): void
    {
        $this->applyCancel($booking, $by, $reason);
    }
}
