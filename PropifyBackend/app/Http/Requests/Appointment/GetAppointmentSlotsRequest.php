<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

final class GetAppointmentSlotsRequest extends FormRequest
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
            'listing_id' => ['required', 'integer', 'exists:listings,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'listing_id.required' => 'Vui lòng truyền listing_id.',
            'listing_id.integer'  => 'listing_id phải là số nguyên.',
            'listing_id.exists'   => 'Listing không tồn tại.',
        ];
    }
}
