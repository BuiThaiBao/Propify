<?php

namespace App\Http\Requests\Listing;

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
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ];
    }

    public function messages(): array
    {
        return [
            'package_id.required'    => 'Vui lòng chọn gói tin.',
            'package_id.exists'      => 'Gói tin không tồn tại.',
            'duration_days.required' => 'Vui lòng chọn thời hạn.',
            'duration_days.min'      => 'Thời hạn phải lớn hơn hoặc bằng 1 ngày.',
            'duration_days.max'      => 'Thời hạn không được vượt quá 3650 ngày.',
        ];
    }
}
