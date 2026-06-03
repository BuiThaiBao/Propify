<?php

namespace App\Http\Requests\Listing;

use Illuminate\Foundation\Http\FormRequest;

final class StoreListingLockAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Vui lòng nhập lý do phản ánh.',
            'reason.min' => 'Lý do phản ánh cần tối thiểu 10 ký tự.',
            'reason.max' => 'Lý do phản ánh không được vượt quá 1000 ký tự.',
        ];
    }
}
