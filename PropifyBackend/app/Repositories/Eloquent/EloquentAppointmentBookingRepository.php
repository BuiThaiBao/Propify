<?php

namespace App\Repositories\Eloquent;

use App\Models\AppointmentBooking;
use App\Repositories\AppointmentBookingRepository;

final class EloquentAppointmentBookingRepository implements AppointmentBookingRepository
{
    public function __construct(
        protected readonly AppointmentBooking $model
    ) {
    }

    public function create(array $data): AppointmentBooking
    {
        return $this->model->newQuery()->create($data);
    }
}
