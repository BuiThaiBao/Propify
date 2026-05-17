<?php

namespace App\Http\Requests\Amenity;

use App\DTOs\Amenity\UpdateListingAmenitiesDto;
use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateListingAmenitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amenities' => ['required', 'array'],
            'amenities.*.amenity_id' => ['required', 'integer', 'distinct', 'exists:attributes,id'],
            'amenities.*.is_visible' => ['required', 'boolean'],
            'amenities.*.display_order' => ['required', 'integer', 'min:0'],
            'amenities.*.is_featured' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $ids = collect($this->input('amenities', []))
                    ->pluck('amenity_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($ids->isEmpty()) {
                    return;
                }

                $validIds = Attribute::query()
                    ->whereIn('id', $ids)
                    ->whereHas('group', fn ($query) => $query->where('code', 'amenities'))
                    ->pluck('id');

                $invalidIds = $ids->diff($validIds);

                if ($invalidIds->isNotEmpty()) {
                    $validator->errors()->add(
                        'amenities',
                        'Danh sách tiện ích chứa attribute không thuộc nhóm amenities: '.$invalidIds->implode(', ')
                    );
                }
            },
        ];
    }

    public function toDto(): UpdateListingAmenitiesDto
    {
        return new UpdateListingAmenitiesDto(
            amenities: collect($this->validated('amenities'))
                ->map(fn (array $amenity) => [
                    'amenity_id' => (int) $amenity['amenity_id'],
                    'is_visible' => (bool) $amenity['is_visible'],
                    'display_order' => (int) $amenity['display_order'],
                    'is_featured' => (bool) $amenity['is_featured'],
                ])
                ->values()
                ->all(),
        );
    }
}
