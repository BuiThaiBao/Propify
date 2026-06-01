<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MapListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $property = $this->property;
        $thumbnail = $this->images?->first();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'demand_type' => $this->demand_type,
            'price' => $property?->price,
            'area' => $property?->area,
            'address' => $property?->address_detail,
            'latitude' => $property?->lat,
            'longitude' => $property?->lng,
            'thumbnail' => $thumbnail?->image_url,
        ];
    }
}
