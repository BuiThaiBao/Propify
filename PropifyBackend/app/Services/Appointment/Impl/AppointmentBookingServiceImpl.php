<?php

namespace App\Services\Appointment\Impl;

use App\DTOs\Appointment\CreateBookingDto;
use App\Enums\BookingStatus;
use App\Exceptions\BookingDuplicateException;
use App\Exceptions\BookingSlotNotFoundException;
use App\Exceptions\BookingInvalidDateException;
use App\Exceptions\BookingSelfSlotException;
use App\Models\AppointmentBooking;
use App\Models\AppointmentSlot;
use App\Repositories\AppointmentBookingRepository;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final class AppointmentBookingServiceImpl implements \App\Services\Appointment\AppointmentBookingService
{
    public function __construct(
        private readonly AppointmentBookingRepository $bookingRepository,
    ) {
    }

    public function createBooking(CreateBookingDto $dto): AppointmentBooking
    {
        // 1. Kiểm tra slot có tồn tại và đang active không
        $slot = AppointmentSlot::query()
            ->where('id', $dto->slotId)
            ->where('is_active', true)
            ->first();

        if (!$slot) {
            throw new BookingSlotNotFoundException();
        }

        // 2. Kiểm tra viewer không được là poster (không tự đặt lịch chính mình)
        if ($dto->viewerId === $slot->poster_id) {
            throw new BookingSelfSlotException();
        }

        // 3. Tính meet_time = date + start_time của slot
        $meetTime = Carbon::parse($dto->date . ' ' . $slot->start_time);
        $now = CarbonImmutable::now();
        $today = CarbonImmutable::today();

        // 4. Kiểm tra ngày chọn phải nằm trong tương lai, tối đa 14 ngày
        $maxDate = $today->addDays(14);
        $meetDate = Carbon::parse($dto->date)->startOfDay();

        if ($meetDate->lt($today) || $meetDate->gt($maxDate)) {
            throw new BookingInvalidDateException('Ngày hẹn phải nằm trong khoảng từ hôm nay đến 14 ngày tới.');
        }

        // 5. Kiểm tra ngày chọn phải đúng day_of_week của slot
        // Carbon: Monday=1 ... Sunday=7 (ISO), trùng với convention trong DB
        $selectedDayOfWeek = Carbon::parse($dto->date)->dayOfWeekIso;
        if ($selectedDayOfWeek !== $slot->day_of_week) {
            throw new BookingInvalidDateException('Ngày bạn chọn không khớp với ngày trong tuần của khung giờ này.');
        }

        // 6. Kiểm tra phải đặt trước ít nhất 2 tiếng
        if ($meetTime->diffInHours($now, absolute: false) > -2) {
            throw new BookingInvalidDateException('Bạn cần đặt lịch trước ít nhất 2 tiếng so với giờ hẹn.');
        }

        // 7. Kiểm tra trùng lịch: cùng viewer + cùng slot + cùng meet_time
        $exists = AppointmentBooking::query()
            ->where('viewer_id', $dto->viewerId)
            ->where('slot_id', $dto->slotId)
            ->where('meet_time', $meetTime)
            ->where('is_deleted', false)
            ->exists();

        if ($exists) {
            throw new BookingDuplicateException();
        }

        // 8. Tạo booking
        return $this->bookingRepository->create([
            'slot_id'      => $dto->slotId,
            'viewer_id'    => $dto->viewerId,
            'meet_time'    => $meetTime,
            'full_name'    => $dto->fullName,
            'phone_number' => $dto->phone,
            'email'        => $dto->email,
            'note'         => $dto->note,
            'status'       => BookingStatus::PENDING->value,
            'is_deleted'   => false,
        ]);
    }

    public function getViewerBookings(int $viewerId): Collection
    {
        return $this->bookingRepository->getByViewerId($viewerId);
    }

    public function getPosterBookings(int $posterId): Collection
    {
        return $this->bookingRepository->getByPosterId($posterId);
    }
}
