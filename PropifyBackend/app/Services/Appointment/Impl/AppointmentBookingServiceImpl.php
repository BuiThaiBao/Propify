<?php

namespace App\Services\Appointment\Impl;

use App\DTOs\Appointment\CreateBookingDto;
use App\Enums\BookingStatus;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Jobs\AutoCancelExpiredBookingJob;
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
            throw new BusinessException(ErrorCode::BookingSlotNotFound);
        }

        // 2. Kiểm tra viewer không được là poster (không tự đặt lịch chính mình)
        if ($dto->viewerId === $slot->poster_id) {
            throw new BusinessException(ErrorCode::BookingSelfSlot);
        }

        // 3. Tính meet_time = date + start_time của slot
        $meetTime = Carbon::parse($dto->date . ' ' . $slot->start_time);
        $now = CarbonImmutable::now();
        $today = CarbonImmutable::today();

        // 4. Kiểm tra ngày chọn phải nằm trong tương lai, tối đa 14 ngày
        $maxDate = $today->addDays(14);
        $meetDate = Carbon::parse($dto->date)->startOfDay();

        if ($meetDate->lt($today) || $meetDate->gt($maxDate)) {
            throw new BusinessException(ErrorCode::BookingInvalidDate);
        }

        // 5. Kiểm tra ngày chọn phải đúng day_of_week của slot
        $selectedDayOfWeek = Carbon::parse($dto->date)->dayOfWeekIso;
        if ($selectedDayOfWeek !== $slot->day_of_week) {
            throw new BusinessException(ErrorCode::BookingInvalidDate);
        }

        // 6. Kiểm tra phải đặt trước ít nhất 2 tiếng
        if ($meetTime->diffInHours($now, absolute: false) > -2) {
            throw new BusinessException(ErrorCode::BookingInvalidDate);
        }

        // 7. Kiểm tra trùng lịch: cùng viewer + cùng slot + cùng meet_time
        $exists = AppointmentBooking::query()
            ->where('viewer_id', $dto->viewerId)
            ->where('slot_id', $dto->slotId)
            ->where('meet_time', $meetTime)
            ->where('is_deleted', false)
            ->exists();

        if ($exists) {
            throw new BusinessException(ErrorCode::BookingDuplicate);
        }

        // 8. Mỗi khách chỉ 01 lịch chưa hoàn thành trên cùng 1 listing
        $existsOnListing = AppointmentBooking::query()
            ->where('viewer_id', $dto->viewerId)
            ->where('is_deleted', false)
            ->where('status', BookingStatus::PENDING->value)
            ->whereHas('slot', function ($query) use ($slot) {
                $query->where('listing_id', $slot->listing_id);
            })
            ->exists();

        if ($existsOnListing) {
            throw new BusinessException(ErrorCode::BookingExistsOnListing);
        }

        // 9. Tính confirm_deadline và is_urgent (AF-01: Đặt lịch gấp)
        $hoursUntilMeet = $now->diffInHours($meetTime, absolute: false);
        $isUrgent = $hoursUntilMeet < 6;

        if ($isUrgent) {
            // Đặt gấp: Time-out = (T_hẹn - T_đặt) - 1 giờ
            $confirmDeadline = $now->addHours(max($hoursUntilMeet - 1, 1));
        } else {
            // Bình thường: deadline = meet_time - 2 giờ
            $confirmDeadline = $meetTime->copy()->subHours(2);
        }

        // 10. Tạo booking
        $booking = $this->bookingRepository->create([
            'slot_id'          => $dto->slotId,
            'viewer_id'        => $dto->viewerId,
            'meet_time'        => $meetTime,
            'full_name'        => $dto->fullName,
            'phone_number'     => $dto->phone,
            'email'            => $dto->email,
            'note'             => $dto->note,
            'status'           => BookingStatus::PENDING->value,
            'is_deleted'       => false,
            'confirm_deadline' => $confirmDeadline,
            'is_urgent'        => $isUrgent,
        ]);

        // 11. Dispatch job tự động hủy khi quá hạn confirm_deadline
        AutoCancelExpiredBookingJob::dispatch($booking->id)
            ->delay($confirmDeadline);

        return $booking;
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
