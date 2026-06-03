<?php

namespace App\Http\Requests\Appointment;

use App\Support\ListingPostingOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer'],
            'status' => ['required', 'string', Rule::in(ListingPostingOptions::values('poster_booking_statuses'))],
            'note' => ['required_if:status,CANCELLED_BY_POSTER', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'booking_id.required' => 'Vui lòng truyền booking_id.',
            'booking_id.integer' => 'booking_id phải là số nguyên.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái chỉ được là APPROVED hoặc CANCELLED_BY_POSTER.',
            'note.required_if' => 'Vui lòng nhập lý do khi từ chối lịch hẹn.',
            'note.max' => 'Ghi chú tối đa 500 ký tự.',
        ];
    }
}
