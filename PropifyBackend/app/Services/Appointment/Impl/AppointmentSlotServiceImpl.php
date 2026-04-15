<?php

namespace App\Services\Appointment\Impl;

use App\DTOs\Appointment\GetAppointmentSlotsDto;
use App\Exceptions\AppointmentSlotNotFoundException;
use App\Repositories\AppointmentSlotRepository;
use App\Services\Appointment\AppointmentSlotService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final class AppointmentSlotServiceImpl implements AppointmentSlotService
{
    public function __construct(
        private readonly AppointmentSlotRepository $appointmentSlotRepository
    ) {
    }

    public function getSlotsByListingAndPoster(GetAppointmentSlotsDto $dto): array
    {
        $slots = $this->appointmentSlotRepository->getByListingAndPoster($dto->listingId, $dto->posterId);

        if ($slots->isEmpty()) {
            throw new AppointmentSlotNotFoundException();
        }

        return $this->buildDateSlots($slots);
    }

    /**
     * Chuyển đổi danh sách slot (chứa day_of_week) thành danh sách ngày cụ thể
     * cho tuần hiện tại và tuần sau, gộp các khung giờ theo từng ngày.
     *
     * @param  Collection<int, \App\Models\AppointmentSlot>  $slots
     * @return array<int, array{date: string, slots: array}>
     */
    private function buildDateSlots(Collection $slots): array
    {
        $today = CarbonImmutable::today();

        // Lấy ngày Thứ Hai đầu tuần hiện tại (ISO week: Monday = 1)
        $startOfCurrentWeek = $today->startOfWeek(Carbon::MONDAY);

        // Gom slot theo day_of_week
        $slotsByDayOfWeek = $slots->groupBy('day_of_week');

        $result = [];

        // Lặp qua 2 tuần: tuần hiện tại (offset=0) và tuần sau (offset=1)
        foreach ([0, 1] as $weekOffset) {
            $weekStart = $startOfCurrentWeek->addWeeks($weekOffset);

            foreach ($slotsByDayOfWeek as $dayOfWeek => $daySlots) {
                // day_of_week: 1=T2(Monday), 2=T3(Tuesday), ..., 7=CN(Sunday)
                // Carbon Monday=1, nên offset = dayOfWeek - 1
                $date = $weekStart->addDays($dayOfWeek - 1);

                // Bỏ qua các ngày đã qua (trước hôm nay)
                if ($date->lt($today)) {
                    continue;
                }

                $dateString = $date->toDateString(); // Y-m-d

                $formattedSlots = $daySlots->map(function ($slot) {
                    return [
                        'id'          => $slot->id,
                        'day_of_week' => $slot->day_of_week,
                        'start_time'  => $slot->start_time,
                        'end_time'    => $slot->end_time,
                        'is_active'   => $slot->is_active,
                    ];
                })->values()->toArray();

                $result[] = [
                    'date'  => $dateString,
                    'slots' => $formattedSlots,
                ];
            }
        }

        // Sắp xếp theo ngày tăng dần
        usort($result, fn($a, $b) => strcmp($a['date'], $b['date']));

        return $result;
    }
}
