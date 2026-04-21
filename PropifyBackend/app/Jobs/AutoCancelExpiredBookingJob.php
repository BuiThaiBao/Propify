<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\AppointmentBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class AutoCancelExpiredBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Số lần thử lại nếu job thất bại.
     */
    public int $tries = 3;

    public function __construct(
        private readonly int $bookingId,
    ) {
    }

    /**
     * Khi job được thực thi (đúng lúc confirm_deadline),
     * kiểm tra nếu booking vẫn PENDING thì tự động hủy.
     */
    public function handle(): void
    {
        $booking = AppointmentBooking::find($this->bookingId);

        if (!$booking) {
            Log::info("[AutoCancel] Booking #{$this->bookingId} không tồn tại, bỏ qua.");
            return;
        }

        // Chỉ hủy nếu vẫn đang ở trạng thái PENDING
        if ($booking->status !== BookingStatus::PENDING->value) {
            Log::info("[AutoCancel] Booking #{$this->bookingId} đã chuyển sang trạng thái [{$booking->status}], bỏ qua.");
            return;
        }

        $existingNote = $booking->note ? $booking->note . ' | ' : '';

        $booking->update([
            'status' => BookingStatus::CANCELLED->value,
            'note'   => $existingNote . '[Tự động hủy] Đặt lịch thất bại vì chủ tin chưa xác nhận.',
        ]);

        Log::info("[AutoCancel] Booking #{$this->bookingId} đã bị hủy tự động do quá hạn xác nhận.");
    }
}
