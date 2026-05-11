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
            'package_id'    => ['required', 'integer', 'exists:packages,id'],
            'duration_days' => ['required', 'integer', 'in:3,7,10,15,30'],
        ];
    }

    public function messages(): array
    {
        return [
            'package_id.required'    => 'Vui lòng chọn gói tin.',
            'package_id.exists'      => 'Gói tin không tồn tại.',
            'duration_days.required' => 'Vui lòng chọn thời hạn.',
            'duration_days.in'       => 'Thời hạn phải là 3, 7, 10, 15 hoặc 30 ngày.',
        ];
    }
}
