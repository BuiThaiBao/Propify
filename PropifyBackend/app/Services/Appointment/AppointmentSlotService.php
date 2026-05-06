<?php

namespace App\Services\Appointment;

use App\DTOs\Appointment\CreateSlotsDto;
use App\DTOs\Appointment\GetAppointmentSlotsDto;
use App\DTOs\Appointment\UpdateSlotDto;
use App\Models\AppointmentSlot;
use Illuminate\Database\Eloquent\Collection;

interface AppointmentSlotService
{
    /**
     * Get all active appointment slots for a listing uploaded by a specific poster.
     * Trả về danh sách ngày cụ thể (tuần hiện tại + tuần sau) với các slot tương ứng.
     *
     * @return array<int, array{date: string, slots: array}>
     */
    public function getSlotsByListingAndPoster(GetAppointmentSlotsDto $dto): array;

    /**
     * Tạo nhiều khung giờ hẹn cùng lúc (bulk create).
     * Kiểm tra không trùng day_of_week + start_time + end_time.
     *
     * @return Collection<int, \App\Models\AppointmentSlot>
     */
    public function createSlots(CreateSlotsDto $dto): Collection;

    /**
     * Cập nhật khung giờ hẹn (day_of_week, start_time, end_time).
     * Tự động hủy các booking PENDING và ghi chú thay đổi.
     */
    public function updateSlot(UpdateSlotDto $dto): AppointmentSlot;

    /**
     * Vô hiệu hóa (xóa mềm) một khung giờ.
     */
    public function disableSlot(int $slotId, int $posterId): bool;
}

