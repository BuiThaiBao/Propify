<?php

namespace App\Http\Resources\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

final class CancelBookingRequest extends FormRequest
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
            'reason'     => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'booking_id.required' => 'Vui lòng truyền booking_id.',
            'reason.required'     => 'Vui lòng nhập lý do hủy lịch.',
            'reason.min'          => 'Lý do hủy quá ngắn (tối thiểu 5 ký tự).',
            'reason.max'          => 'Lý do hủy tối đa 500 ký tự.',
        ];
    }
}
