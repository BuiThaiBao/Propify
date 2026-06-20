<?php

namespace App\Services\Appointment\State;

use App\Models\AppointmentBooking;

/**
 * APPROVED: đã được chủ nhà xác nhận. Chỉ còn cho phép hủy (cancel);
 * không cho confirm/reject lại (kế thừa hành vi ném lỗi từ lớp cha).
 */
final class ApprovedState extends AbstractBookingState
{
    public function cancel(AppointmentBooking $booking, BookingRole $by, string $reason): void
    {
        $this->applyCancel($booking, $by, $reason);
    }
}
