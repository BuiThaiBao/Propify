<?php

namespace App\Repositories\Eloquent;

use App\Models\AppointmentBooking;
use App\Repositories\AppointmentBookingRepository;
use Illuminate\Database\Eloquent\Collection;

final class EloquentAppointmentBookingRepository implements AppointmentBookingRepository
{
    public function __construct(
        protected readonly AppointmentBooking $model
    ) {}

    public function create(array $data): AppointmentBooking
    {
        return $this->model->newQuery()->create($data);
    }

    public function getByViewerId(int $viewerId): Collection
    {
        return $this->model->newQuery()
            ->where('viewer_id', $viewerId)
            ->where('is_deleted', false)
            ->with(['slot.listing'])
            ->orderByDesc('id')
            ->get();
    }

    public function getByPosterId(int $posterId): Collection
    {
        return $this->model->newQuery()
            ->whereHas('slot', function ($query) use ($posterId) {
                $query->where('poster_id', $posterId);
            })
            ->where('is_deleted', false)
            ->with(['slot.listing'])
            ->orderByDesc('id')
            ->get();
    }
}
