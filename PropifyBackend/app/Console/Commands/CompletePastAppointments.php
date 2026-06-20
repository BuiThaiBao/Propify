<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\AppointmentBooking;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Tự động hoàn thành các lịch hẹn đã xác nhận (APPROVED) khi đã qua giờ kết thúc hẹn.
 *
 * Quy tắc: lịch APPROVED có khung hẹn (start_time → end_time) của slot, nếu thời điểm
 * kết thúc (ngày hẹn + end_time của slot) đã trôi qua thì chuyển sang COMPLETED.
 * Ví dụ: slot 08:00–09:00, lịch đã xác nhận, sau 09:00 cùng ngày → Hoàn thành.
 */
final class CompletePastAppointments extends Command
{
    protected $signature = 'appointments:complete-past';

    protected $description = 'Đánh dấu HOÀN THÀNH cho các lịch hẹn đã xác nhận đã qua giờ kết thúc.';

    public function handle(): int
    {
        $now = Carbon::now();
        $completed = 0;

        AppointmentBooking::query()
            ->where('status', BookingStatus::APPROVED->value)
            ->where('is_deleted', false)
            ->where('meet_time', '<=', $now) // giờ bắt đầu đã qua → mới có khả năng kết thúc
            ->with('slot')
            ->chunkById(200, function ($bookings) use ($now, &$completed) {
                foreach ($bookings as $booking) {
                    if (! $booking->slot) {
                        continue;
                    }

                    $endAt = Carbon::parse($booking->meet_time->toDateString().' '.$booking->slot->end_time);

                    if ($endAt->lessThanOrEqualTo($now)) {
                        // Chuyển trạng thái qua State (ApprovedState::complete) cho nhất quán.
                        $booking->state()->complete($booking);
                        $booking->save();
                        $completed++;
                    }
                }
            });

        $this->info("Completed {$completed} past appointment(s).");

        return self::SUCCESS;
    }
}
