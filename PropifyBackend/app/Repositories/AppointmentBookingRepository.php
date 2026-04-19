<?php

namespace App\Repositories;

use App\Models\AppointmentBooking;

interface AppointmentBookingRepository
{
    /**
     * Tạo mới một booking.
     */
    public function create(array $data): AppointmentBooking;

    /**
     * Lấy danh sách booking của một viewer theo thứ tự status PENDING, APPROVED, CANCELLED.
     */
    public function getByViewerId(int $viewerId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Lấy danh sách booking của các khách hàng đã đặt cho lịch của chủ nhà (poster).
     */
    public function getByPosterId(int $posterId): \Illuminate\Database\Eloquent\Collection;
}
