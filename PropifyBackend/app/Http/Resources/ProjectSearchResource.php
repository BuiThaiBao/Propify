<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProjectSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'province' => $this->province,
            'ward' => $this->ward,
            'street_code' => $this->street_code,
            'project_name' => $this->project_name,
            'address_detail' => $this->address_detail,
            'active_listings_count' => (int) ($this->active_listings_count ?? 0),
            'relevance' => (float) ($this->search_relevance ?? 0),
        ];
    }
}
