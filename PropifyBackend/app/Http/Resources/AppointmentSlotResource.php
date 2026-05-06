<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AppointmentSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'listing_id'  => $this->listing_id,
            'poster_id'   => $this->poster_id,
            'day_of_week' => $this->day_of_week,
            'start_time'  => $this->start_time,
            'end_time'    => $this->end_time,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),

            // Dữ liệu liên quan
            'listing' => $this->whenLoaded('listing', fn () => [
                'id'          => $this->listing->id,
                'title'       => $this->listing->title,
                'status'      => $this->listing->status,
                'property'    => $this->whenLoaded('listing', fn () => $this->listing->property ? [
                    'id'             => $this->listing->property->id,
                    'type'           => $this->listing->property->type,
                    'province_code'  => $this->listing->property->province_code,
                    'address_detail' => $this->listing->property->address_detail,
                ] : null),
            ]),

            'poster' => $this->whenLoaded('poster', fn () => [
                'id'        => $this->poster->id,
                'full_name' => $this->poster->full_name,
                'phone'     => $this->poster->phone,
                'email'     => $this->poster->email,
            ]),
        ];
    }
}
