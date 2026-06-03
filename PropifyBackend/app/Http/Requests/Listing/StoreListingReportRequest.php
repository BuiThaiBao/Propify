<?php

namespace App\Http\Requests\Listing;

use App\Support\ListingPostingOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreListingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reasons' => ['required', 'array', 'min:1'],
            'reasons.*' => ['required', 'string', Rule::in(ListingPostingOptions::values('listing_report_reasons'))],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reasons.required' => 'Vui lòng chọn lý do báo cáo.',
            'reasons.array' => 'Lý do báo cáo không hợp lệ.',
            'reasons.min' => 'Vui lòng chọn ít nhất một lý do báo cáo.',
            'reasons.*.in' => 'Lý do báo cáo không hợp lệ.',
            'description.max' => 'Nội dung mô tả không được vượt quá 1000 ký tự.',
        ];
    }
}
