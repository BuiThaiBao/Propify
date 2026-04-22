<?php

namespace App\Http\Controllers\Api\V1\Appointment;

use App\Helpers\ApiResponse;
use App\Http\Resources\AppointmentBookingResource;
use App\Http\Resources\Requests\Appointment\CreateBookingRequest;
use App\Http\Resources\ViewerBookingResource;
use App\Services\Appointment\AppointmentBookingService;
use Illuminate\Http\JsonResponse;

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
        $viewerId = (int) auth('api')->id();
        $bookings = $this->bookingService->getViewerBookings($viewerId);

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
        $posterId = (int) auth('api')->id();
        $bookings = $this->bookingService->getPosterBookings($posterId);

        return ApiResponse::success(
            data: ViewerBookingResource::collection($bookings),
            message: 'Lấy danh sách lịch hẹn nhận được thành công.'
        );
    }
}
