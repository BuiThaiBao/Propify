<?php

namespace App\Http\Requests\Amenity;

use App\DTOs\Amenity\UpdateAmenityDto;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:255'],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toDto(): UpdateAmenityDto
    {
        return new UpdateAmenityDto(
            name: $this->validated('name'),
            description: $this->validated('description'),
            icon: $this->validated('icon'),
            orderIndex: (int) ($this->validated('order_index') ?? 0),
            isActive: (bool) ($this->validated('is_active') ?? true),
        );
    }
}
