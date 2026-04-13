<?php

namespace App\Http\Controllers\Api\V1\Appointment;

use App\Helpers\ApiResponse;
use App\Http\Requests\Appointment\GetAppointmentSlotsRequest;
use App\Http\Resources\AppointmentSlotResource;
use App\Services\Appointment\AppointmentSlotService;
use Illuminate\Http\JsonResponse;

final class AppointmentSlotController
{
    public function __construct(
        private readonly AppointmentSlotService $appointmentSlotService
    ) {
    }

    /**
     * Lấy danh sách appointment slots.
     * Request body: { "listing_id": 1 }
     */
    public function index(GetAppointmentSlotsRequest $request): JsonResponse
    {
        $listingId = (int) $request->input('listing_id');
        $posterId  = auth('api')->id();

        $slots = $this->appointmentSlotService->getSlotsByListingAndPoster($listingId, $posterId);

        return ApiResponse::success(
            data: AppointmentSlotResource::collection($slots),
            message: 'Lấy danh sách lịch hẹn thành công.'
        );
    }
}


