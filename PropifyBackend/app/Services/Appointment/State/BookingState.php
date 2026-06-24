<?php

namespace App\Services\Appointment\State;

use App\Exceptions\BusinessException;
use App\Models\AppointmentBooking;

/**
 * Trạng thái của một booking (State pattern). Mỗi trạng thái tự biết thao tác nào
 * hợp lệ và chuyển sang đâu — thay cho các khối if (status == ...) rải rác.
 *
 * Các thao tác chỉ thay đổi thuộc tính trên model trong bộ nhớ; việc persist (save)
 * và phát event do tầng Command/Service đảm nhận.
 */
interface BookingState
{
    /** Chủ nhà xác nhận (PENDING → APPROVED). */
    public function confirm(AppointmentBooking $booking): void;

    /** Chủ nhà từ chối (PENDING → CANCELLED_BY_POSTER), ghi lý do vào note nếu có. */
    public function reject(AppointmentBooking $booking, ?string $note): void;

    /**
     * Khách hoặc chủ nhà hủy (→ CANCELLED_BY_VIEWER / CANCELLED_BY_POSTER).
     * Áp quy tắc 2 giờ (BR-01).
     *
     * @throws BusinessException
     */
    public function cancel(AppointmentBooking $booking, BookingRole $by, string $reason): void;

    /**
     * Hệ thống tự hoàn thành khi đã qua giờ kết thúc hẹn (APPROVED → COMPLETED).
     *
     * @throws BusinessException
     */
    public function complete(AppointmentBooking $booking): void;
}
