<?php

namespace App\Services\Appointment\Booking;

use App\Enums\BookingStatus;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\AppointmentBooking;
use Carbon\Carbon;

/**
 * Kiểm tra nghiệp vụ khi tạo booking. Chạy lần lượt các điều kiện theo đúng thứ tự;
 * điều kiện đầu tiên không thoả sẽ ném BusinessException và dừng.
 */
final class BookingValidator
{
    public function validate(BookingContext $ctx): void
    {
        $this->ensureListingActive($ctx);
        $this->ensureNotSelfBooking($ctx);
        $this->ensureValidDateRange($ctx);
        $this->ensureMatchDayOfWeek($ctx);
        $this->ensureMinLeadTime($ctx);
        $this->ensureNoDuplicate($ctx);
        $this->ensureOnePendingPerListing($ctx);
    }

    /** Tin đăng của slot phải đang ACTIVE. */
    private function ensureListingActive(BookingContext $ctx): void
    {
        if (! $ctx->slot->listing || $ctx->slot->listing->status !== 'ACTIVE') {
            throw new BusinessException(ErrorCode::ListingNotActive);
        }
    }

    /** Người xem không được tự đặt lịch trên slot của chính mình. */
    private function ensureNotSelfBooking(BookingContext $ctx): void
    {
        if ($ctx->dto->viewerId === $ctx->slot->poster_id) {
            throw new BusinessException(ErrorCode::BookingSelfSlot);
        }
    }

    /** Ngày hẹn phải nằm trong khoảng [hôm nay, hôm nay + 14 ngày]. */
    private function ensureValidDateRange(BookingContext $ctx): void
    {
        $maxDate = $ctx->today->addDays(14);
        $meetDate = Carbon::parse($ctx->dto->date)->startOfDay();

        if ($meetDate->lt($ctx->today) || $meetDate->gt($maxDate)) {
            throw new BusinessException(ErrorCode::BookingInvalidDate);
        }
    }

    /** Ngày hẹn phải đúng thứ trong tuần (ISO) mà slot mở. */
    private function ensureMatchDayOfWeek(BookingContext $ctx): void
    {
        $selectedDayOfWeek = Carbon::parse($ctx->dto->date)->dayOfWeekIso;

        if ($selectedDayOfWeek !== $ctx->slot->day_of_week) {
            throw new BusinessException(ErrorCode::BookingInvalidDate);
        }
    }

    /** Phải đặt trước giờ hẹn ít nhất 2 tiếng. */
    private function ensureMinLeadTime(BookingContext $ctx): void
    {
        if ($ctx->meetTime->diffInHours($ctx->now, absolute: false) > -2) {
            throw new BusinessException(ErrorCode::BookingInvalidDate);
        }
    }

    /** Không trùng lịch: cùng người xem + slot + giờ hẹn đang PENDING/APPROVED. */
    private function ensureNoDuplicate(BookingContext $ctx): void
    {
        $exists = AppointmentBooking::query()
            ->where('viewer_id', $ctx->dto->viewerId)
            ->where('slot_id', $ctx->dto->slotId)
            ->where('meet_time', $ctx->meetTime)
            ->where('is_deleted', false)
            ->whereIn('status', [
                BookingStatus::PENDING->value,
                BookingStatus::APPROVED->value,
            ])
            ->exists();

        if ($exists) {
            throw new BusinessException(ErrorCode::BookingDuplicate);
        }
    }

    /** Mỗi khách chỉ có tối đa 01 lịch đang chờ (PENDING) trên cùng một tin đăng. */
    private function ensureOnePendingPerListing(BookingContext $ctx): void
    {
        $slot = $ctx->slot;

        $existsOnListing = AppointmentBooking::query()
            ->where('viewer_id', $ctx->dto->viewerId)
            ->where('is_deleted', false)
            ->where('status', BookingStatus::PENDING->value)
            ->whereHas('slot', function ($query) use ($slot) {
                $query->where('listing_id', $slot->listing_id);
            })
            ->exists();

        if ($existsOnListing) {
            throw new BusinessException(ErrorCode::BookingExistsOnListing);
        }
    }
}
