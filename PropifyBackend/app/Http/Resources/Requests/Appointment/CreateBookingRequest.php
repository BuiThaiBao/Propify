<?php

namespace App\Http\Resources\Requests\Appointment;

use App\DTOs\Appointment\CreateBookingDto;
use Illuminate\Foundation\Http\FormRequest;

final class CreateBookingRequest extends FormRequest
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
            'slot_id'   => ['required', 'integer'],
            'date'      => ['required', 'date_format:Y-m-d'],
            'full_name' => ['required', 'string', 'max:100'],
            'phone'     => ['required', 'string', 'regex:/^(0|\+84)(3[2-9]|5[2689]|7[06-9]|8[1-9]|9[0-9])\d{7}$/'],
            'email'     => ['required', 'email', 'max:100'],
            'note'      => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slot_id.required'     => 'Vui lòng chọn khung giờ hẹn.',
            'slot_id.integer'      => 'slot_id phải là số nguyên.',
            'date.required'        => 'Vui lòng chọn ngày hẹn.',
            'date.date_format'     => 'Ngày hẹn phải có định dạng Y-m-d (VD: 2026-04-20).',
            'full_name.required'   => 'Vui lòng nhập họ tên.',
            'full_name.max'        => 'Họ tên tối đa 100 ký tự.',
            'phone.required'       => 'Vui lòng nhập số điện thoại.',
            'phone.regex'          => 'Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại Việt Nam (VD: 0901234567).',
            'email.required'       => 'Vui lòng nhập email.',
            'email.email'          => 'Email không hợp lệ.',
            'email.max'            => 'Email tối đa 100 ký tự.',
            'note.max'             => 'Ghi chú tối đa 500 ký tự.',
        ];
    }

    public function toDto(): CreateBookingDto
    {
        return new CreateBookingDto(
            slotId:   (int) $this->input('slot_id'),
            viewerId: (int) auth('api')->id(),
            date:     $this->input('date'),
            fullName: $this->input('full_name'),
            phone:    $this->input('phone'),
            email:    $this->input('email'),
            note:     $this->input('note'),
        );
    }
}
