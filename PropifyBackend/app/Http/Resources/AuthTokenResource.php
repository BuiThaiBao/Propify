<?php

namespace App\Http\Resources;

use App\DTOs\Auth\AuthResultDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuthTokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AuthResultDto $this */
        return [
            'user' => [
                'id' => $this->userId,
                'full_name' => $this->fullName,
                'role' => $this->role,
            ],
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
        ];
    }
}
