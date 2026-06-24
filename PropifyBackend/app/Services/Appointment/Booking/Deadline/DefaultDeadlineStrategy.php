<?php

namespace App\Services\Appointment\Booking\Deadline;

use App\Services\Appointment\Booking\BookingContext;
use Carbon\CarbonImmutable;

/**
 * Quy tắc mặc định (SRS trang 68 - BR-02): hạn xác nhận là 6 tiếng kể từ lúc đặt.
 */
final class DefaultDeadlineStrategy implements DeadlineStrategy
{
    public function deadlineFor(BookingContext $ctx): CarbonImmutable
    {
        return $ctx->now->addHours(6);
    }
}
