<?php

namespace App\Http\Resources\Requests\Auth\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class CheckResetOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'otp'   => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Vui lòng nhập email.',
            'email.exists'   => 'Email không tồn tại.',
            'otp.required'   => 'Vui lòng nhập mã OTP.',
            'otp.size'       => 'Mã OTP phải đúng 6 ký tự.',
        ];
    }
}
