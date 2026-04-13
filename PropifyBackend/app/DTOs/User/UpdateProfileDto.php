<?php

namespace App\DTOs\User;

use Illuminate\Http\Request;

final readonly class UpdateProfileDto
{
    public function __construct(
        public string $fullName,
        public ?string $phone,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            fullName: $request->input('full_name'),
            phone: $request->input('phone')
        );
    }
}