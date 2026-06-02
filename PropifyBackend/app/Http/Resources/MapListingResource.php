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
            'province' => $property?->province,
            'ward' => $property?->ward,
            'latitude' => $property?->lat,
            'longitude' => $property?->lng,
            'bedrooms' => $property?->bedrooms,
            'bathrooms' => $property?->bathrooms,
            'poster_type' => $property?->poster_type,
            'thumbnail' => $thumbnail?->image_url,
        ];
    }
}
