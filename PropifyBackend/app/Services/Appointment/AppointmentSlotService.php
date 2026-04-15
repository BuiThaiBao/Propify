<?php

namespace App\Services\Appointment;

use App\DTOs\Appointment\GetAppointmentSlotsDto;

interface AppointmentSlotService
{
    /**
     * Get all active appointment slots for a listing uploaded by a specific poster.
     * Trả về danh sách ngày cụ thể (tuần hiện tại + tuần sau) với các slot tương ứng.
     *
     * @return array<int, array{date: string, slots: array}>
     */
    public function getSlotsByListingAndPoster(GetAppointmentSlotsDto $dto): array;
}
