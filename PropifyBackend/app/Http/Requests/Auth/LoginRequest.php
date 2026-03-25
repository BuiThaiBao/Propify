<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Vui lòng nhập địa chỉ email.',
            'email.email'       => 'Địa chỉ email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.string'   => 'Mật khẩu phải là chuỗi ký tự.',
            'password.min'      => 'Mật khẩu phải có ít nhất :min ký tự.',
        ];
    }
}
