<?php

namespace App\Http\Controllers\Api\V1\Appointment;

use App\Helpers\ApiResponse;
use App\Http\Requests\Appointment\GetAppointmentSlotsRequest;
use App\Services\Appointment\AppointmentSlotService;
use Illuminate\Http\JsonResponse;

final class AppointmentSlotController
{
    public function __construct(
        private readonly AppointmentSlotService $appointmentSlotService
    ) {
    }

    /**
     * Lấy danh sách appointment slots theo ngày cụ thể (tuần hiện tại + tuần sau).
     * Request body: { "listing_id": 1 }
     */
    public function index(GetAppointmentSlotsRequest $request): JsonResponse
    {
        $dto = $request->toDto();

        $dateSlots = $this->appointmentSlotService->getSlotsByListingAndPoster($dto);

        return ApiResponse::success(
            data: $dateSlots,
            message: 'Lấy danh sách lịch hẹn thành công.'
        );
    }
}
