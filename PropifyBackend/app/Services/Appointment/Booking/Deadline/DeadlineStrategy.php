<?php

namespace App\Services\Appointment\Booking\Deadline;

use App\Services\Appointment\Booking\BookingContext;
use Carbon\CarbonImmutable;

/**
 * Chiến lược tính hạn xác nhận (confirm_deadline) cho một booking.
 */
interface DeadlineStrategy
{
    public function deadlineFor(BookingContext $ctx): CarbonImmutable;
}
