<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender'          => $this->whenLoaded('sender', fn () => [
                'id'         => $this->sender->id,
                'full_name'  => $this->sender->full_name,
                'avatar_url' => $this->sender->avatar_url,
            ]),
            'type'     => $this->type?->value ?? $this->type,
            'body'     => $this->body,
            'file_url' => $this->file_url,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
