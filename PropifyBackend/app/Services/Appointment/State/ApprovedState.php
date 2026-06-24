<?php

namespace App\Services\Appointment\State;

use App\Enums\BookingStatus;
use App\Models\AppointmentBooking;

/**
 * APPROVED: đã được chủ nhà xác nhận. Cho phép hủy (cancel) và hoàn thành (complete);
 * không cho confirm/reject lại (kế thừa hành vi ném lỗi từ lớp cha).
 */
final class ApprovedState extends AbstractBookingState
{
    public function cancel(AppointmentBooking $booking, BookingRole $by, string $reason): void
    {
        $this->applyCancel($booking, $by, $reason);
    }

    public function complete(AppointmentBooking $booking): void
    {
        $booking->status = BookingStatus::COMPLETED->value;
    }
}
