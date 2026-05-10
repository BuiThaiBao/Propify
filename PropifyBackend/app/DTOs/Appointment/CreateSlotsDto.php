<?php

namespace App\DTOs\Appointment;

readonly class CreateSlotsDto
{
    /**
     * @param  array<int, array{day_of_week: int, start_time: string, end_time: string}>  $slots
     */
    public function __construct(
        public int   $listingId,
        public int   $posterId,
        public array $slots,
    ) {
    }
}
