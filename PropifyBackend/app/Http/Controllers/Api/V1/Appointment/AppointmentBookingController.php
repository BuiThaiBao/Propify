<?php

namespace App\Http\Controllers\Api\V1\Appointment;

use App\Helpers\ApiResponse;
use App\Http\Requests\Appointment\CreateBookingRequest;
use App\Http\Requests\Appointment\UpdateBookingStatusRequest;
use App\Http\Requests\Appointment\CancelBookingRequest;
use App\Http\Resources\AppointmentBookingResource;
use App\Http\Resources\ViewerBookingResource;
use App\Services\Appointment\AppointmentBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AppointmentBookingController
{
    public function __construct(
        private readonly AppointmentBookingService $bookingService
    ) {
    }

    /**
     * Đặt lịch hẹn xem nhà.
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $dto = $request->toDto();

        $booking = $this->bookingService->createBooking($dto);

        // Load slot relationship để resource có thể trả về start_time, end_time
        $booking->load('slot');

        return ApiResponse::created(
            data: new AppointmentBookingResource($booking),
            message: 'Đặt lịch hẹn thành công.'
        );
    }

    /**
     * Lấy danh sách lịch hẹn của tôi (viewer).
     */
    public function index(): JsonResponse
    {
        $bookings = $this->bookingService->getViewerBookings();

        return ApiResponse::success(
            data: ViewerBookingResource::collection($bookings),
            message: 'Lấy danh sách lịch hẹn thành công.'
        );
    }

    /**
     * Lấy danh sách lịch hẹn nhận được của chủ nhà/môi giới (poster).
     */
    public function received(): JsonResponse
    {
        $bookings = $this->bookingService->getPosterBookings();

        return ApiResponse::success(
            data: ViewerBookingResource::collection($bookings),
            message: 'Lấy danh sách lịch hẹn nhận được thành công.'
        );
    }

    /**
     * Chủ nhà xác nhận (APPROVED) hoặc từ chối (CANCELLED) lịch hẹn.
     */
    public function updateStatus(UpdateBookingStatusRequest $request): JsonResponse
    {
        $booking = $this->bookingService->updateBookingStatus(
            bookingId: (int) $request->input('booking_id'),
            status:    $request->input('status'),
            note:      $request->input('note'),
        );

        return ApiResponse::success(
            data: new ViewerBookingResource($booking),
            message: 'Cập nhật trạng thái lịch hẹn thành công.'
        );
    }

    /**
     * Hủy lịch hẹn (dành cho cả khách và chủ nhà).
     */
    public function cancel(CancelBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->cancelBooking(
            bookingId: (int) $request->input('booking_id'),
            reason:    $request->input('reason'),
        );

        return ApiResponse::success(
            data: new ViewerBookingResource($booking),
            message: 'Hủy lịch hẹn thành công.'
        );
    }
}
