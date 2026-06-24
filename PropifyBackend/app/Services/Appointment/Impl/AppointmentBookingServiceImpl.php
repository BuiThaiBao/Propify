<?php

namespace App\Services\Appointment\Impl;

use App\DTOs\Appointment\CreateBookingDto;
use App\Enums\BookingStatus;
use App\Enums\ErrorCode;
use App\Events\Appointment\AppointmentBooked;
use App\Exceptions\BusinessException;
use App\Jobs\AutoCancelExpiredBookingJob;
use App\Models\AppointmentBooking;
use App\Models\AppointmentSlot;
use App\Repositories\AppointmentBookingRepository;
use App\Services\Appointment\AppointmentBookingService;
use App\Services\Appointment\Booking\BookingContext;
use App\Services\Appointment\Booking\BookingValidator;
use App\Services\Appointment\Booking\Deadline\DefaultDeadlineStrategy;
use App\Services\Appointment\Booking\Deadline\UrgentDeadlineStrategy;
use App\Services\Appointment\Command\CancelBookingCommand;
use App\Services\Appointment\Command\ConfirmBookingCommand;
use App\Services\Appointment\Command\RejectBookingCommand;
use App\Services\Appointment\State\BookingRole;
use Illuminate\Database\Eloquent\Collection;

final class AppointmentBookingServiceImpl implements AppointmentBookingService
{
    public function __construct(
        private readonly AppointmentBookingRepository $bookingRepository,
        private readonly BookingValidator $validator,
        private readonly ConfirmBookingCommand $confirmCommand,
        private readonly RejectBookingCommand $rejectCommand,
        private readonly CancelBookingCommand $cancelCommand,
    ) {}

    public function createBooking(CreateBookingDto $dto): AppointmentBooking
    {
        // 1. Kiểm tra slot có tồn tại và đang active không
        $slot = AppointmentSlot::query()
            ->where('id', $dto->slotId)
            ->where('is_active', true)
            ->first();

        if (! $slot) {
            throw new BusinessException(ErrorCode::BookingSlotNotFound);
        }

        // 2. Chuỗi kiểm tra nghiệp vụ (Chain of Responsibility):
        //    listing active → không tự đặt → ngày hợp lệ → đúng thứ →
        //    đặt trước ≥ 2h → không trùng → tối đa 1 PENDING/listing.
        $ctx = BookingContext::create($dto, $slot);
        $this->validator->validate($ctx);

        // 3. Tính hạn xác nhận (Strategy): đặt gấp (<6h) vs mặc định.
        $strategy = $ctx->isUrgent()
            ? new UrgentDeadlineStrategy
            : new DefaultDeadlineStrategy;
        $confirmDeadline = $strategy->deadlineFor($ctx);

        // 4. Dựng dữ liệu booking PENDING rồi lưu.
        $booking = $this->bookingRepository->create([
            'slot_id' => $ctx->dto->slotId,
            'viewer_id' => $ctx->dto->viewerId,
            'meet_time' => $ctx->meetTime,
            'full_name' => $ctx->dto->fullName,
            'phone_number' => $ctx->dto->phone,
            'email' => $ctx->dto->email,
            'note' => $ctx->dto->note,
            'status' => BookingStatus::PENDING->value,
            'is_deleted' => false,
            'confirm_deadline' => $confirmDeadline,
            'is_urgent' => $ctx->isUrgent(),
        ]);

        // 5. Tự động hủy khi quá hạn confirm_deadline + phát event đặt lịch.
        AutoCancelExpiredBookingJob::dispatch($booking->id)
            ->delay($confirmDeadline);

        AppointmentBooked::dispatch($booking->id);

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
        $booking = $this->findActiveBookingOrFail($bookingId);

        // 2. Kiểm tra người gọi API phải là poster (chủ nhà) của slot
        $slot = AppointmentSlot::find($booking->slot_id);
        if (! $slot || $slot->poster_id !== $posterId) {
            throw new BusinessException(ErrorCode::BookingNotOwner);
        }

        // 3. Điều phối theo status (Command). State sẽ tự kiểm tra booking đang PENDING;
        //    nếu không, ném BookingNotPending — giữ nguyên hành vi gốc.
        match ($status) {
            BookingStatus::APPROVED->value => $this->confirmCommand->execute($booking),
            BookingStatus::CANCELLED_BY_POSTER->value => $this->rejectCommand->execute($booking, $note),
            default => throw new BusinessException(ErrorCode::BookingNotPending),
        };

        // 4. Load relationships để resource trả về đầy đủ
        $booking->load('slot.listing');

        return $booking;
    }

    public function cancelBooking(int $bookingId, int $userId, string $reason): AppointmentBooking
    {
        // 1. Tìm booking
        $booking = $this->findActiveBookingOrFail($bookingId);

        // 2. Xác định vai trò của người thực hiện hủy
        $slot = AppointmentSlot::find($booking->slot_id);
        $isViewer = ($booking->viewer_id === $userId);
        $isPoster = ($slot && $slot->poster_id === $userId);

        if (! $isViewer && ! $isPoster) {
            throw new BusinessException(ErrorCode::BookingNotOwner);
        }

        // 3. Hủy qua Command + State (kiểm tra trạng thái hợp lệ + quy tắc 2 giờ, ghi note).
        $role = $isViewer ? BookingRole::VIEWER : BookingRole::POSTER;
        $this->cancelCommand->execute($booking, $role, $reason);

        $booking->load('slot.listing');

        return $booking;
    }

    /**
     * Tìm booking chưa bị xóa mềm, ném BookingNotFound nếu không có.
     */
    private function findActiveBookingOrFail(int $bookingId): AppointmentBooking
    {
        $booking = AppointmentBooking::query()
            ->where('id', $bookingId)
            ->where('is_deleted', false)
            ->first();

        if (! $booking) {
            throw new BusinessException(ErrorCode::BookingNotFound);
        }

        return $booking;
    }
}
