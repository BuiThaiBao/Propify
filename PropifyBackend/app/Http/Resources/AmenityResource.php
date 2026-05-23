<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AmenityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'order_index' => $this->order_index,
            'group' => $this->whenLoaded('group', fn () => [
                'id' => $this->group->id,
                'name' => $this->group->name,
                'code' => $this->group->code,
                'input_type' => $this->group->input_type,
            ]),
            'is_visible' => $this->whenPivotLoaded('property_attributes', fn () => (bool) $this->pivot->is_visible),
            'display_order' => $this->whenPivotLoaded('property_attributes', fn () => (int) $this->pivot->display_order),
            'is_featured' => $this->whenPivotLoaded('property_attributes', fn () => (bool) $this->pivot->is_featured),
        ];
    }
}
