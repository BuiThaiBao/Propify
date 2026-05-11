<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'demand_type' => $this->demand_type,
            'status' => $this->status,
            'package' => $this->package ? [
                'id'       => $this->package->id,
                'name'     => $this->package->name,
                'slug'     => $this->package->slug,
                'badge'    => $this->package->badge,
                'color'    => $this->package->color,
                'priority' => $this->package->priority,
            ] : null,
            'is_verified' => $this->is_verified,
            'views' => $this->views ?? 0,
            'request_verification' => $this->request_verification,
            'appointment_at' => $this->appointment_at?->toIso8601String(),
            'appointment_days' => $this->appointment_days ?? [],
            'appointment_time_slot' => $this->appointment_time_slot,
            'appointment_contact_name' => $this->appointment_contact_name,
            'appointment_contact_phone' => $this->appointment_contact_phone,
            'appointment_contact_email' => $this->appointment_contact_email,
            'appointment_note' => $this->appointment_note,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'property' => [
                'id' => $this->property?->id,
                'type' => $this->property?->type,
                'province_code' => $this->property?->province_code,
                'ward_code' => $this->property?->ward_code,
                'street_code' => $this->property?->street_code,
                'project_name' => $this->property?->project_name,
                'address_detail' => $this->property?->address_detail,
                'area' => $this->property?->area,
                'price' => $this->property?->price,
                'is_negotiable' => $this->property?->is_negotiable,
                'bedrooms' => $this->property?->bedrooms,
                'bathrooms' => $this->property?->bathrooms,
                'floors' => $this->property?->floors,
                'floor_number' => $this->property?->floor_number,
                'balconies' => $this->property?->balconies,
                'facade_width' => $this->property?->facade_width,
                'depth' => $this->property?->depth,
                'road_width' => $this->property?->road_width,
                'direction_code' => $this->property?->direction_code,
                'balcony_direction_code' => $this->property?->balcony_direction_code,
                'furniture_status' => $this->property?->furniture_status,
                'contact_name' => $this->property?->contact_name,
                'contact_phone' => $this->property?->contact_phone,
                'contact_email' => $this->property?->contact_email,
                'poster_type' => $this->property?->poster_type,
                'amenities' => $this->property?->amenities ?? [],
                'legal_paper_types' => $this->property?->legal_paper_types ?? [],
                'public_info_agreed' => (bool) ($this->property?->public_info_agreed ?? false),
                'lat' => $this->property?->lat,
                'lng' => $this->property?->lng,
                'attributes' => $this->property?->attributes?->map(fn ($attribute) => [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'group' => $attribute->group?->name,
                    'group_code' => $attribute->group?->code,
                ])->values() ?? [],
            ],
            'owner' => [
                'id' => $this->owner?->id,
                'full_name' => $this->owner?->full_name,
                'avatar_url' => $this->owner?->avatar_url,
                'email' => $this->owner?->email,
            ],
            'images' => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->image_url,
                'is_thumbnail' => $image->is_thumbnail,
                'sort_order' => $image->sort_order,
            ])->values(),
            'videos' => $this->videos->map(fn ($video) => [
                'id' => $video->id,
                'url' => $video->video_url,
                'provider' => $video->provider,
            ])->values(),
            'verification_documents' => $this->verificationDocuments->map(fn ($document) => [
                'id' => $document->id,
                'type' => $document->document_type,
                'url' => $document->file_url,
            ])->values(),
            'appointments' => $this->appointments->map(fn ($appointment) => [
                'id' => $appointment->id,
                'meet_time' => $appointment->meet_time?->toIso8601String(),
                'contact_name' => $appointment->contact_name,
                'contact_phone' => $appointment->contact_phone,
                'contact_email' => $appointment->contact_email,
                'note' => $appointment->note,
                'status' => $appointment->status,
            ])->values(),
        ];
    }
}
