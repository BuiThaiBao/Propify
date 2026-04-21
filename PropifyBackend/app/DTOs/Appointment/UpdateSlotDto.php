<?php

namespace App\DTOs\Appointment;

readonly class UpdateSlotDto
{
    public function __construct(
        public int    $slotId,
        public int    $listingId,
        public int    $posterId,
        public int    $newDayOfWeek,
        public string $newStartTime,
        public string $newEndTime,
    ) {
    }
}
