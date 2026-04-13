<?php

namespace App\Http\Requests\User;

use App\DTOs\User\UpdateProfileDto;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Họ tên không được để trống',
            'full_name.max' => 'Họ tên không được vượt quá 100 ký tự',
            'phone.max' => 'Số điện thoại không được vượt quá 10 ký tự',
        ];
    }
    public function toDto(): UpdateProfileDto
    {
        return UpdateProfileDto::fromRequest($this);
    }
}
