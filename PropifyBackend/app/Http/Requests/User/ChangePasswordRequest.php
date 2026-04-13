<?php

namespace App\Http\Requests\User;

use App\DTOs\User\ChangePasswordDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'confirmed',
                'different:current_password',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
            'new_password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
            'new_password.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
            'new_password.min' => 'Mật khẩu mới phải có ít nhất :min ký tự.',
        ];
    }
    public function toDto(): ChangePasswordDto
    {
        return ChangePasswordDto::fromRequest($this);
    }
}