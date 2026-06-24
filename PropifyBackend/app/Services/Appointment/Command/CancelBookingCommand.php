<?php

namespace App\Services\Appointment\Command;

use App\Models\AppointmentBooking;
use App\Services\Appointment\State\BookingRole;

/**
 * Khách hoặc chủ nhà hủy lịch hẹn (áp quy tắc 2 giờ qua State).
 *
 * Lưu ý: logic gốc của cancelBooking KHÔNG phát event — giữ nguyên ở đây.
 */
final class CancelBookingCommand
{
    public function execute(AppointmentBooking $booking, BookingRole $by, string $reason): void
    {
        $booking->state()->cancel($booking, $by, $reason);
        $booking->save();
    }
}
