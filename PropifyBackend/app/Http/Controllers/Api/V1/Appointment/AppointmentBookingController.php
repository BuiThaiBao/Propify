<?php

namespace App\Http\Controllers\Api\V1\Appointment;

use App\Helpers\ApiResponse;
use App\Http\Requests\Appointment\CreateBookingRequest;
use App\Http\Resources\AppointmentBookingResource;
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
}
