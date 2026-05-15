<?php

namespace App\Http\Requests\Appointment;

use App\DTOs\Appointment\CreateSlotsDto;
use Illuminate\Foundation\Http\FormRequest;

final class CreateSlotsRequest extends FormRequest
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
            'listing_id'         => ['required', 'integer', 'exists:listings,id'],
            'slots'              => ['required', 'array', 'min:1'],
            'slots.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'slots.*.start_time' => ['required', 'regex:/^\d{1,2}:\d{2}$/'],
            'slots.*.end_time'   => ['required', 'regex:/^\d{1,2}:\d{2}$/', 'after:slots.*.start_time'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'listing_id.required'           => 'Vui lòng truyền listing_id.',
            'listing_id.integer'            => 'listing_id phải là số nguyên.',
            'listing_id.exists'             => 'Listing không tồn tại.',
            'slots.required'                => 'Vui lòng thêm ít nhất 1 khung giờ.',
            'slots.array'                   => 'Slots phải là mảng.',
            'slots.min'                     => 'Vui lòng thêm ít nhất 1 khung giờ.',
            'slots.*.day_of_week.required'  => 'Vui lòng chọn ngày trong tuần.',
            'slots.*.day_of_week.integer'   => 'Ngày trong tuần phải là số nguyên.',
            'slots.*.day_of_week.between'   => 'Ngày trong tuần phải từ 1 (Thứ 2) đến 7 (Chủ nhật).',
            'slots.*.start_time.required'   => 'Vui lòng nhập giờ bắt đầu.',
            'slots.*.start_time.regex'      => 'Giờ bắt đầu phải có định dạng HH:mm (VD: 09:00).',
            'slots.*.end_time.required'     => 'Vui lòng nhập giờ kết thúc.',
            'slots.*.end_time.regex'        => 'Giờ kết thúc phải có định dạng HH:mm (VD: 10:00).',
            'slots.*.end_time.after'        => 'Giờ kết thúc phải sau giờ bắt đầu.',
        ];
    }

    public function toDto(): CreateSlotsDto
    {
        return new CreateSlotsDto(
            listingId: (int) $this->input('listing_id'),
            posterId:  (int) auth('api')->id(),
            slots:     $this->input('slots') ?? [],
        );
    }
}
