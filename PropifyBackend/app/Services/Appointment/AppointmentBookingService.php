<?php

namespace App\Services\Appointment;

use App\DTOs\Appointment\CreateBookingDto;
use App\Models\AppointmentBooking;
use Illuminate\Database\Eloquent\Collection;

interface AppointmentBookingService
{
    /**
     * Tạo mới một booking lịch hẹn xem nhà.
     *
     * @throws \App\Exceptions\AppointmentSlotNotFoundException
     * @throws \App\Exceptions\BookingSelfSlotException
     * @throws \App\Exceptions\BookingInvalidDateException
     */
    public function createBooking(CreateBookingDto $dto): AppointmentBooking;

    /**
     * Lấy danh sách lịch hẹn của người xem (viewer).
     *
     * @return Collection<int, AppointmentBooking>
     */
    public function getViewerBookings(int $viewerId): Collection;

    /**
     * Lấy danh sách lịch hẹn mà người tạo (poster) nhận được.
     *
     * @return Collection<int, AppointmentBooking>
     */
    public function getPosterBookings(int $posterId): Collection;
}
