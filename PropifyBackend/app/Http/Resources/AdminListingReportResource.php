<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdminListingReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reporter = $this['reporter'] ?? null;

        return [
            'id' => $this['id'],
            'report_group_id' => $this['report_group_id'],
            'listing_id' => $this['listing_id'],
            'reporter' => $reporter ? [
                'id' => $reporter->id,
                'full_name' => $reporter->full_name,
                'avatar_url' => $reporter->avatar_url,
                'email' => $reporter->email,
            ] : null,
            'reasons' => $this['reasons'],
            'reason_labels' => $this['reason_labels'],
            'description' => $this['description'],
            'image_urls' => $this['image_urls'],
            'status' => $this['status'],
            'report_count' => $this['report_count'],
            'created_at' => $this['created_at']?->toIso8601String(),
            'updated_at' => $this['updated_at']?->toIso8601String(),
        ];
    }
}
