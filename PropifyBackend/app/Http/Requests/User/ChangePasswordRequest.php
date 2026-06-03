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
        $user = $this->user();
        $hasPassword = $user && ! is_null($user->password);

        return [
            'current_password' => $hasPassword ? ['required', 'string'] : ['nullable', 'string'],
            'new_password' => array_values(array_filter([
                'required',
                'string',
                'confirmed',
                $hasPassword ? 'different:current_password' : null,
                Password::min(8)->letters()->mixedCase()->numbers(),
            ])),
        ];
    }

    /**
     * Trim password trước khi validate (BR.ACC.03)
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'current_password' => trim($this->input('current_password', '')),
            'new_password' => trim($this->input('new_password', '')),
            'new_password_confirmation' => trim($this->input('new_password_confirmation', '')),
        ]);
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
