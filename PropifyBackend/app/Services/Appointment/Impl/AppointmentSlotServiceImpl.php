<?php

namespace App\Services\Appointment\Impl;

use App\DTOs\Appointment\CreateSlotsDto;
use App\DTOs\Appointment\GetAppointmentSlotsDto;
use App\DTOs\Appointment\UpdateSlotDto;
use App\Enums\BookingStatus;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\AppointmentBooking;
use App\Models\AppointmentSlot;
use App\Models\Listing;
use App\Repositories\AppointmentSlotRepository;
use App\Services\Appointment\AppointmentSlotService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final class AppointmentSlotServiceImpl implements AppointmentSlotService
{
    public function __construct(
        private readonly AppointmentSlotRepository $appointmentSlotRepository
    ) {
    }

    public function getSlotsByListing(GetAppointmentSlotsDto $dto): array
    {
        $slots = $this->appointmentSlotRepository->getByListingId($dto->listingId);

        if ($slots->isEmpty()) {
            throw new BusinessException(ErrorCode::AppointmentSlotNotFound);
        }

        // Kiểm tra Listing phải ACTIVE
        $listing = $slots->first()->listing;
        if (!$listing || $listing->status !== 'ACTIVE') {
            throw new BusinessException(ErrorCode::ListingNotActive);
        }

        return $this->buildDateSlots($slots);
    }

    public function updateSlot(UpdateSlotDto $dto): AppointmentSlot
    {
        // 1. Kiểm tra slot có tồn tại và đang active không
        $slot = AppointmentSlot::query()
            ->where('id', $dto->slotId)
            ->where('is_active', true)
            ->first();

        if (!$slot) {
            throw new BusinessException(ErrorCode::AppointmentSlotNotFound);
        }

        // 1.1 Kiểm tra Listing phải ACTIVE
        if (!$slot->listing || $slot->listing->status !== 'ACTIVE') {
            throw new BusinessException(ErrorCode::ListingNotActive);
        }

        // 2. Kiểm tra poster_id (người gọi API phải là chủ slot)
        if ($slot->poster_id !== $dto->posterId) {
            throw new BusinessException(ErrorCode::SlotNotOwner);
        }

        // 3. Kiểm tra listing_id có khớp với slot không
        if ($slot->listing_id !== $dto->listingId) {
            throw new BusinessException(ErrorCode::SlotListingMismatch);
        }

        // 4. Kiểm tra trùng khung giờ trên cùng listing + cùng day_of_week (trừ slot hiện tại)
        $overlap = AppointmentSlot::query()
            ->where('listing_id', $dto->listingId)
            ->where('poster_id', $dto->posterId)
            ->where('day_of_week', $dto->newDayOfWeek)
            ->where('is_active', true)
            ->where('id', '!=', $dto->slotId)
            ->where(function ($query) use ($dto) {
                // Trùng khi: start_time mới < end_time cũ AND end_time mới > start_time cũ
                $query->where('start_time', '<', $dto->newEndTime)
                      ->where('end_time', '>', $dto->newStartTime);
            })
            ->exists();

        if ($overlap) {
            throw new BusinessException(ErrorCode::SlotTimeOverlap);
        }

        // 5. Hủy tất cả booking PENDING và ghi note (booking APPROVED giữ nguyên không động vào)
        $pendingBookings = AppointmentBooking::query()
            ->where('slot_id', $dto->slotId)
            ->where('is_deleted', false)
            ->where('status', BookingStatus::PENDING->value)
            ->get();
        $oldInfo = "Thứ {$slot->day_of_week}, {$slot->start_time} - {$slot->end_time}";
        $newInfo = "Thứ {$dto->newDayOfWeek}, {$dto->newStartTime} - {$dto->newEndTime}";

        foreach ($pendingBookings as $booking) {
            $cancelNote = "[Tự động hủy] Chủ nhà đã thay đổi lịch hẹn từ ({$oldInfo}) sang ({$newInfo}).";
            $existingNote = $booking->note ? $booking->note . ' | ' : '';

            AppointmentBooking::query()
                ->where('id', $booking->id)
                ->update([
                    'status' => BookingStatus::CANCELLED->value,
                    'note'   => $existingNote . $cancelNote,
                ]);
        }

        // 6. Cập nhật slot
        $slot->update([
            'day_of_week' => $dto->newDayOfWeek,
            'start_time'  => $dto->newStartTime,
            'end_time'    => $dto->newEndTime,
        ]);

        return $slot->fresh();
    }

    /**
     * Chuyển đổi danh sách slot (chứa day_of_week) thành danh sách ngày cụ thể
     * cho tuần hiện tại và tuần sau, gộp các khung giờ theo từng ngày.
     *
     * @param  Collection<int, \App\Models\AppointmentSlot>  $slots
     * @return array<int, array{date: string, slots: array}>
     */
    private function buildDateSlots(Collection $slots): array
    {
        $today = CarbonImmutable::today();
        $now = Carbon::now();

        // Gom slot theo day_of_week
        $slotsByDayOfWeek = $slots->groupBy('day_of_week');

        $result = [];

        // Lặp qua 14 ngày kể từ hôm nay (ngày 0 = hôm nay, ngày 13 = ngày thứ 14)
        for ($dayOffset = 0; $dayOffset < 14; $dayOffset++) {
            $date = $today->addDays($dayOffset);

            // Tính day_of_week theo chuẩn ISO: 1=T2(Monday)...7=CN(Sunday)
            $dayOfWeek = (int) $date->dayOfWeekIso;

            // Bỏ qua nếu không có slot nào cho thứ này
            if (!$slotsByDayOfWeek->has($dayOfWeek)) {
                continue;
            }

            $dateString = $date->toDateString(); // Y-m-d
            $daySlots = $slotsByDayOfWeek->get($dayOfWeek);

            $formattedSlots = $daySlots->filter(function ($slot) use ($dateString, $today, $now) {
                // Nếu là ngày hôm nay, chỉ hiển thị slot cách giờ hiện tại ít nhất 2 tiếng
                if ($dateString === $today->toDateString()) {
                    $slotStartTime = Carbon::parse($dateString . ' ' . $slot->start_time);
                    return $now->diffInHours($slotStartTime, false) >= 2;
                }
                return true;
            })->map(function ($slot) {
                return [
                    'id'          => $slot->id,
                    'day_of_week' => $slot->day_of_week,
                    'start_time'  => $slot->start_time,
                    'end_time'    => $slot->end_time,
                    'is_active'   => $slot->is_active,
                ];
            })->values()->toArray();

            // Chỉ thêm ngày có ít nhất 1 slot hợp lệ
            if (!empty($formattedSlots)) {
                $result[] = [
                    'date'  => $dateString,
                    'slots' => $formattedSlots,
                ];
            }
        }

        return $result;
    }

    public function createSlots(CreateSlotsDto $dto): Collection
    {
        // 1. Kiểm tra Listing tồn tại (tạm thời không ràng buộc ACTIVE để thông luồng đăng tin)
        $listing = Listing::query()
            ->where('id', $dto->listingId)
            ->first();

        if (!$listing) {
            throw new BusinessException(ErrorCode::AppointmentSlotNotFound);
        }

        // 2. Kiểm tra poster_id có match với listing owner không
        if ($listing->owner_id !== $dto->posterId) {
            throw new BusinessException(ErrorCode::SlotNotOwner);
        }

        // 3. Kiểm tra không trùng trong array được gửi lên
        $this->validateSlotsForDuplicates($dto->slots);

        // 4. Kiểm tra không trùng với các slot đã tồn tại trong DB
        $this->validateSlotsAgainstExisting($dto->listingId, $dto->posterId, $dto->slots);

        // 5. Bulk insert
        $createdSlots = [];
        foreach ($dto->slots as $slot) {
            $createdSlot = AppointmentSlot::create([
                'listing_id'    => $dto->listingId,
                'poster_id'     => $dto->posterId,
                'day_of_week'   => $slot['day_of_week'],
                'start_time'    => $slot['start_time'],
                'end_time'      => $slot['end_time'],
                'is_active'     => true,
            ]);

            $createdSlots[] = $createdSlot;
        }

        return collect($createdSlots);
    }

    /**
     * Kiểm tra không trùng day_of_week + start_time + end_time trong array slots.
     *
     * @param  array<int, array{day_of_week: int, start_time: string, end_time: string}>  $slots
     *
     * @throws BusinessException nếu có trùng
     */
    private function validateSlotsForDuplicates(array $slots): void
    {
        $seen = [];

        foreach ($slots as $index => $slot) {
            $key = "{$slot['day_of_week']}_{$slot['start_time']}_{$slot['end_time']}";

            if (isset($seen[$key])) {
                throw new BusinessException(ErrorCode::SlotTimeOverlap);
            }

            $seen[$key] = true;
        }
    }

    /**
     * Kiểm tra không trùng với các slot đã tồn tại trong DB.
     *
     * @param  array<int, array{day_of_week: int, start_time: string, end_time: string}>  $slots
     *
     * @throws BusinessException nếu có trùng
     */
    private function validateSlotsAgainstExisting(int $listingId, int $posterId, array $slots): void
    {
        foreach ($slots as $newSlot) {
            $overlap = AppointmentSlot::query()
                ->where('listing_id', $listingId)
                ->where('poster_id', $posterId)
                ->where('day_of_week', $newSlot['day_of_week'])
                ->where('is_active', true)
                ->where(function ($query) use ($newSlot) {
                    // Kiểm tra trùng khung giờ: start < newEnd AND end > newStart
                    $query->where('start_time', '<', $newSlot['end_time'])
                          ->where('end_time', '>', $newSlot['start_time']);
                })
                ->exists();

            if ($overlap) {
                throw new BusinessException(ErrorCode::SlotTimeOverlap);
            }
        }
    }

    public function disableSlot(int $slotId, int $posterId): bool
    {
        $slot = AppointmentSlot::find($slotId);

        if (!$slot) {
            throw new BusinessException(ErrorCode::AppointmentSlotNotFound);
        }

        if ($slot->poster_id !== $posterId) {
            throw new BusinessException(ErrorCode::SlotNotOwner);
        }

        return $slot->update(['is_active' => false]);
    }

}

