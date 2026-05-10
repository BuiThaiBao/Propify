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

        // 1.1 Kiểm tra Listing phải đang ACTIVE
        if (!$slot->listing || $slot->listing->status !== 'ACTIVE') {
            throw new BusinessException(ErrorCode::ListingNotActive);
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
            // AF-01 Đặt lịch gấp (SRS trang 68): Time-out = (T_hẹn - T_đặt) - 1 giờ
            // Đảm bảo tối thiểu 1h để xử lý nếu cực gấp
            $confirmDeadline = $now->addHours(max($hoursUntilMeet - 1, 1));
        } else {
            // Quy tắc mặc định (SRS trang 68 - BR-02): Time-out mặc định để xác nhận là 6 tiếng kể từ lúc đặt
            $confirmDeadline = $now->addHours(6);
        }

        // 10. Tạo booking
        $booking = $this->bookingRepository->create([
            'slot_id' => $dto->slotId,
            'viewer_id' => $dto->viewerId,
            'meet_time' => $meetTime,
            'full_name' => $dto->fullName,
            'phone_number' => $dto->phone,
            'email' => $dto->email,
            'note' => $dto->note,
            'status' => BookingStatus::PENDING->value,
            'is_deleted' => false,
            'confirm_deadline' => $confirmDeadline,
            'is_urgent' => $isUrgent,
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

    public function updateBookingStatus(int $bookingId, int $posterId, string $status, ?string $note): AppointmentBooking
    {
        // 1. Kiểm tra booking có tồn tại không
        $booking = AppointmentBooking::query()
            ->where('id', $bookingId)
            ->where('is_deleted', false)
            ->first();

        if (!$booking) {
            throw new BusinessException(ErrorCode::BookingNotFound);
        }

        // 2. Kiểm tra người gọi API phải là poster (chủ nhà) của slot
        $slot = AppointmentSlot::find($booking->slot_id);
        if (!$slot || $slot->poster_id !== $posterId) {
            throw new BusinessException(ErrorCode::BookingNotOwner);
        }

        // 3. Booking phải đang ở trạng thái PENDING
        if ($booking->status !== BookingStatus::PENDING->value) {
            throw new BusinessException(ErrorCode::BookingNotPending);
        }

        // 4. Cập nhật trạng thái
        $updateData = ['status' => $status];

        if ($status === BookingStatus::CANCELLED_BY_POSTER->value && $note) {
            $existingNote = $booking->note ? $booking->note . ' | ' : '';
            $updateData['note'] = $existingNote . '[Chủ nhà từ chối] ' . $note;
        }

        $booking->update($updateData);

        // 5. Load relationships để resource trả về đầy đủ
        $booking->load('slot.listing');

        return $booking;
    }

    public function cancelBooking(int $bookingId, int $userId, string $reason): AppointmentBooking
    {
        // 1. Tìm booking
        $booking = AppointmentBooking::query()
            ->where('id', $bookingId)
            ->where('is_deleted', false)
            ->first();

        if (!$booking) {
            throw new BusinessException(ErrorCode::BookingNotFound);
        }

        // 2. Xác định vai trò của người thực hiện hủy
        $slot = AppointmentSlot::find($booking->slot_id);
        $isViewer = ($booking->viewer_id === $userId);
        $isPoster = ($slot && $slot->poster_id === $userId);

        if (!$isViewer && !$isPoster) {
            throw new BusinessException(ErrorCode::BookingNotOwner);
        }

        // 3. Kiểm tra trạng thái: chỉ cho hủy khi đang PENDING hoặc APPROVED
        if (!in_array($booking->status, [BookingStatus::PENDING->value, BookingStatus::APPROVED->value])) {
            throw new BusinessException(ErrorCode::BookingNotPending); // Hoặc tạo mã lỗi riêng nếu cần
        }

        // 4. Quy tắc 2 giờ (BR-01)
        $now = Carbon::now();
        $meetTime = Carbon::parse($booking->meet_time);
        if ($now->diffInHours($meetTime, false) < 2) {
            throw new BusinessException(ErrorCode::BookingTooLateToCancel);
        }

        // 5. Cập nhật trạng thái và note
        $roleLabel = $isViewer ? 'Khách thuê' : 'Chủ nhà';
        $existingNote = $booking->note ? $booking->note . ' | ' : '';

        $status = $isViewer ? BookingStatus::CANCELLED_BY_VIEWER->value : BookingStatus::CANCELLED_BY_POSTER->value;

        $booking->update([
            'status' => $status,
            'note' => $existingNote . "[{$roleLabel} hủy] " . $reason,
        ]);

        $booking->load('slot.listing');

        return $booking;
    }
}
