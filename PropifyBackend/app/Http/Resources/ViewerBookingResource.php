<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ViewerBookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'slot_id'       => $this->slot_id,
            'viewer_id'     => $this->viewer_id,
            'full_name'     => $this->full_name,
            'meet_time'     => $this->meet_time?->toDateTimeString(),
            'start_time'    => $this->whenLoaded('slot', fn () => $this->slot->start_time),
            'end_time'      => $this->whenLoaded('slot', fn () => $this->slot->end_time),
            'listing_title' => $this->whenLoaded('slot', fn () => $this->slot->listing?->title),
            'listing_id'    => $this->whenLoaded('slot', fn () => $this->slot->listing_id),
            'email'         => $this->email,
            'phone'         => $this->phone_number,
            'note'          => $this->note,
            'is_deleted'        => $this->is_deleted,
            'status'            => $this->status,
            'confirm_deadline'  => $this->confirm_deadline?->toDateTimeString(),
            'is_urgent'         => $this->is_urgent,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
