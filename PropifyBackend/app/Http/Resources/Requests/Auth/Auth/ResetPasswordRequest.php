<?php

namespace App\Http\Resources\Requests\Auth\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'                 => ['required', 'email', 'exists:users,email'],
            'otp'                   => ['required', 'string', 'size:6'],
            'password'              => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'password_confirmation' => ['required'],
        ];
    }

    /**
     * Trim password trước khi validate (BR.ACC.03)
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('password')) {
            $this->merge([
                'password' => trim($this->input('password')),
                'password_confirmation' => trim($this->input('password_confirmation', '')),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Vui lòng nhập email.',
            'email.exists'      => 'Email này chưa được đăng ký.',
            'otp.required'      => 'Vui lòng nhập mã OTP.',
            'otp.size'          => 'Mã OTP phải đúng 6 ký tự.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min'      => 'Mật khẩu phải có ít nhất :min ký tự.',
        ];
    }
}
