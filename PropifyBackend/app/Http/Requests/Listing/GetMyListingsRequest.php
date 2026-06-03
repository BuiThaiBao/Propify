<?php

namespace App\Http\Requests\Listing;

use App\Support\ListingPostingOptions;
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
            'status' => ['nullable', Rule::in(ListingPostingOptions::values('listing_statuses'))],
            'demand_type' => ['nullable', Rule::in(ListingPostingOptions::values('demand_types'))],
            'keyword' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
