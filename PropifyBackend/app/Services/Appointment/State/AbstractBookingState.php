<?php

namespace App\Services\Appointment\State;

use App\Enums\BookingStatus;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\AppointmentBooking;
use Carbon\Carbon;

/**
 * Mặc định mọi thao tác đều KHÔNG hợp lệ (ném BookingNotPending) — giống hành vi
 * gốc khi booking không ở trạng thái cho phép. Các state con chỉ override thao tác
 * mà chúng cho phép.
 *
 * Gom luôn 2 helper bị lặp nhiều nơi trong service gốc: ghép note và quy tắc 2 giờ.
 */
abstract class AbstractBookingState implements BookingState
{
    public function confirm(AppointmentBooking $booking): void
    {
        throw new BusinessException(ErrorCode::BookingNotPending);
    }

    public function reject(AppointmentBooking $booking, ?string $note): void
    {
        throw new BusinessException(ErrorCode::BookingNotPending);
    }

    public function cancel(AppointmentBooking $booking, BookingRole $by, string $reason): void
    {
        throw new BusinessException(ErrorCode::BookingNotPending);
    }

    /**
     * Ghép note theo định dạng "[{label}] {text}", giữ note cũ nếu có (ngăn cách " | ").
     */
    protected function appendNote(AppointmentBooking $booking, string $label, string $text): void
    {
        $existingNote = $booking->note ? $booking->note.' | ' : '';
        $booking->note = $existingNote."[{$label}] ".$text;
    }

    /**
     * BR-01: chỉ được hủy khi còn ít nhất 2 tiếng trước giờ hẹn.
     */
    protected function guardTwoHourRule(AppointmentBooking $booking): void
    {
        $now = Carbon::now();
        $meetTime = Carbon::parse($booking->meet_time);

        if ($now->diffInHours($meetTime, false) < 2) {
            throw new BusinessException(ErrorCode::BookingTooLateToCancel);
        }
    }

    /**
     * Logic hủy dùng chung cho PENDING và APPROVED: kiểm tra 2 giờ, đặt trạng thái
     * theo vai trò người hủy, ghi note.
     */
    protected function applyCancel(AppointmentBooking $booking, BookingRole $by, string $reason): void
    {
        $this->guardTwoHourRule($booking);

        $booking->status = $by->isViewer()
            ? BookingStatus::CANCELLED_BY_VIEWER->value
            : BookingStatus::CANCELLED_BY_POSTER->value;

        $this->appendNote($booking, $by->label().' hủy', $reason);
    }
}
