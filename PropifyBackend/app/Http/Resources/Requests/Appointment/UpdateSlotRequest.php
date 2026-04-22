<?php

namespace App\Http\Resources\Requests\Appointment;

use App\DTOs\Appointment\UpdateSlotDto;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSlotRequest extends FormRequest
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
            'slot_id'        => ['required', 'integer'],
            'listing_id'     => ['required', 'integer', 'exists:listings,id'],
            'new_day_of_week' => ['required', 'integer', 'between:1,7'],
            'new_start_time' => ['required', 'regex:/^\d{1,2}:\d{2}$/'],
            'new_end_time'   => ['required', 'regex:/^\d{1,2}:\d{2}$/', 'after:new_start_time'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slot_id.required'            => 'Vui lòng truyền slot_id.',
            'slot_id.integer'             => 'slot_id phải là số nguyên.',
            'listing_id.required'         => 'Vui lòng truyền listing_id.',
            'listing_id.integer'          => 'listing_id phải là số nguyên.',
            'listing_id.exists'           => 'Listing không tồn tại.',
            'new_day_of_week.required'    => 'Vui lòng chọn ngày trong tuần.',
            'new_day_of_week.integer'     => 'Ngày trong tuần phải là số nguyên.',
            'new_day_of_week.between'     => 'Ngày trong tuần phải từ 1 (Thứ 2) đến 7 (Chủ nhật).',
            'new_start_time.required'     => 'Vui lòng nhập giờ bắt đầu.',
            'new_start_time.date_format'  => 'Giờ bắt đầu phải có định dạng HH:mm (VD: 09:00).',
            'new_end_time.required'       => 'Vui lòng nhập giờ kết thúc.',
            'new_end_time.date_format'    => 'Giờ kết thúc phải có định dạng HH:mm (VD: 10:00).',
            'new_end_time.after'          => 'Giờ kết thúc phải sau giờ bắt đầu.',
        ];
    }

    public function toDto(): UpdateSlotDto
    {
        return new UpdateSlotDto(
            slotId:       (int) $this->input('slot_id'),
            listingId:    (int) $this->input('listing_id'),
            posterId:     (int) auth('api')->id(),
            newDayOfWeek: (int) $this->input('new_day_of_week'),
            newStartTime: $this->input('new_start_time'),
            newEndTime:   $this->input('new_end_time'),
        );
    }
}
