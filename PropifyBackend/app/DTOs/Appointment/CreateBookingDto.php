<?php

namespace App\DTOs\Appointment;

readonly class CreateBookingDto
{
    public function __construct(
        public int     $slotId,
        public int     $viewerId,
        public string  $date,
        public string  $fullName,
        public string  $phone,
        public string  $email,
        public ?string $note,
    ) {
    }
}
