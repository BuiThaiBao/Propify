<?php

namespace App\Http\Resources\Requests\User;

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
        $userId = $this->user()?->id;

        return [
            'full_name'  => 'required|string|max:100',
            'phone'      => ['nullable', 'string', 'regex:/^[0-9]{10}$/', 'unique:users,phone,' . $userId],
            'avatar_url' => ['nullable', 'string', 'url', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Họ tên không được để trống',
            'full_name.max'      => 'Họ tên không được vượt quá 100 ký tự',
            'phone.regex'        => 'Số điện thoại phải đúng 10 chữ số.',
            'phone.unique'       => 'Số điện thoại này đã được sử dụng.',
            'avatar_url.url'     => 'URL ảnh đại diện không hợp lệ.',
        ];
    }

    public function toDto(): UpdateProfileDto
    {
        return UpdateProfileDto::fromRequest($this);
    }
}

