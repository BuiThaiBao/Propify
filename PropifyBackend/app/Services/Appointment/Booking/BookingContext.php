<?php

namespace App\Services\Appointment\Booking;

use App\DTOs\Appointment\CreateBookingDto;
use App\Models\AppointmentSlot;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Gói toàn bộ dữ liệu đầu vào đã được tính toán sẵn cho luồng tạo booking:
 * DTO gốc, slot đã tải, thời điểm hẹn (meet_time) và mốc thời gian hiện tại.
 *
 * Các Rule (Chain of Responsibility), Strategy tính hạn và Builder đều nhận
 * context này thay vì tự tính lại — đảm bảo dùng chung một bộ giá trị nhất quán.
 */
final class BookingContext
{
    public function __construct(
        public readonly CreateBookingDto $dto,
        public readonly AppointmentSlot $slot,
        public readonly Carbon $meetTime,
        public readonly CarbonImmutable $now,
        public readonly CarbonImmutable $today,
    ) {}

    /**
     * Dựng context từ DTO + slot đã tải. meet_time = ngày hẹn + start_time của slot.
     */
    public static function create(CreateBookingDto $dto, AppointmentSlot $slot): self
    {
        return new self(
            dto: $dto,
            slot: $slot,
            meetTime: Carbon::parse($dto->date.' '.$slot->start_time),
            now: CarbonImmutable::now(),
            today: CarbonImmutable::today(),
        );
    }

    /**
     * Số giờ (có dấu) từ bây giờ đến giờ hẹn — giữ nguyên công thức gốc của service.
     */
    public function hoursUntilMeet(): float
    {
        return $this->now->diffInHours($this->meetTime, absolute: false);
    }

    /**
     * AF-01 (SRS trang 68): đặt gấp khi còn dưới 6 tiếng trước giờ hẹn.
     */
    public function isUrgent(): bool
    {
        return $this->hoursUntilMeet() < 6;
    }
}
