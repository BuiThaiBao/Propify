<?php

namespace App\Http\Controllers\Api\V1\Appointment;

use App\Helpers\ApiResponse;
use App\Http\Requests\Appointment\GetAppointmentSlotsRequest;
use App\Http\Requests\Appointment\UpdateSlotRequest;
use App\Http\Resources\AppointmentSlotResource;
use App\Services\Appointment\AppointmentSlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    /**
     * Cập nhật khung giờ hẹn (day_of_week, start_time, end_time).
     */
    public function update(UpdateSlotRequest $request): JsonResponse
    {
        $dto = $request->toDto();

        $slot = $this->appointmentSlotService->updateSlot($dto);

        return ApiResponse::success(
            data: new AppointmentSlotResource($slot),
            message: 'Cập nhật khung giờ hẹn thành công.'
        );
    }

    /**
     * Vô hiệu hóa (xóa mềm) khung giờ hẹn.
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'slot_id' => 'required|integer',
        ]);

        $posterId = (int) auth('api')->id();

        $this->appointmentSlotService->disableSlot(
            slotId:   (int) $request->input('slot_id'),
            posterId: $posterId
        );

        return ApiResponse::success(
            message: 'Đã vô hiệu hóa khung giờ hẹn thành công.'
        );
    }
}

