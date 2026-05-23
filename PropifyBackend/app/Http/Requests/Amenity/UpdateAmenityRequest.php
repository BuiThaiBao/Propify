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
            'icon' => ['nullable', 'string', 'max:255'],
            'order_index' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toDto(): UpdateAmenityDto
    {
        return new UpdateAmenityDto(
            name: $this->validated('name'),
            icon: $this->validated('icon'),
            orderIndex: (int) ($this->validated('order_index') ?? 0),
        );
    }
}
