<?php

namespace App\Http\Resources\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;

final class UpgradeListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', 'exists:packages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'package_id.required' => 'Vui lòng chọn gói tin.',
            'package_id.exists'   => 'Gói tin không tồn tại.',
        ];
    }
}
