<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Events\Appointment\AppointmentBookingExpired;
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

    public int $tries = 3;

    public function __construct(
        private readonly int $bookingId,
    ) {}

    public function handle(): void
    {
        $booking = AppointmentBooking::find($this->bookingId);

        if (! $booking) {
            Log::info("[AutoCancel] Booking #{$this->bookingId} không tồn tại, bỏ qua.");

            return;
        }

        if ($booking->status !== BookingStatus::PENDING->value) {
            Log::info("[AutoCancel] Booking #{$this->bookingId} đã chuyển sang trạng thái [{$booking->status}], bỏ qua.");

            return;
        }

        $existingNote = $booking->note ? $booking->note.' | ' : '';

        $booking->update([
            'status' => BookingStatus::EXPIRED->value,
            'note' => $existingNote.'[Tự động hủy] Lịch hẹn đã quá thời gian xác nhận và bị hủy.',
        ]);

        AppointmentBookingExpired::dispatch($booking->id);

        Log::info("[AutoCancel] Booking #{$this->bookingId} đã bị hủy tự động do quá hạn xác nhận.");
    }
}
