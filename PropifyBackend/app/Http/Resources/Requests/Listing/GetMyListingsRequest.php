<?php

namespace App\Http\Resources\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GetMyListingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['DRAFT', 'PENDING', 'ACTIVE', 'EXPIRED', 'REJECTED', 'LOCKED'])],
            'demand_type' => ['nullable', Rule::in(['SALE', 'RENT'])],
            'keyword' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
