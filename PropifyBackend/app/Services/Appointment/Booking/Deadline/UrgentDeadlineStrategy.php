<?php

namespace App\Services\Appointment\Booking\Deadline;

use App\Services\Appointment\Booking\BookingContext;
use Carbon\CarbonImmutable;

/**
 * AF-01 Đặt lịch gấp (SRS trang 68): Time-out = (T_hẹn − T_đặt) − 1 giờ,
 * đảm bảo tối thiểu 1h để xử lý nếu cực gấp.
 */
final class UrgentDeadlineStrategy implements DeadlineStrategy
{
    public function deadlineFor(BookingContext $ctx): CarbonImmutable
    {
        $hoursUntilMeet = $ctx->hoursUntilMeet();

        return $ctx->now->addHours(max($hoursUntilMeet - 1, 1));
    }
}
