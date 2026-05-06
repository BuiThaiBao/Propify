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
     * @throws \App\Exceptions\BusinessException
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

    /**
     * Cập nhật trạng thái lịch hẹn (APPROVED / CANCELLED) bởi chủ nhà.
     */
    public function updateBookingStatus(int $bookingId, int $posterId, string $status, ?string $note): AppointmentBooking;

    /**
     * Hủy một lịch hẹn đã được duyệt (APPROVED) bởi khách thuê hoặc chủ nhà.
     * Quy tắc: Chỉ được hủy trước giờ hẹn tối thiểu 2 tiếng.
     */
    public function cancelBooking(int $bookingId, int $userId, string $reason): AppointmentBooking;
}
