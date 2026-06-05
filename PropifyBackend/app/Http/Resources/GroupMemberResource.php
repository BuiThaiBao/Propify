<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class GroupMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user->id,
            'full_name' => $this->user->full_name,
            'avatar_url' => $this->user->avatar_url,
            'role' => $this->role?->value ?? 'member',
            'nickname' => $this->nickname,
            'joined_at' => $this->joined_at?->toIso8601String(),
        ];
    }
}
