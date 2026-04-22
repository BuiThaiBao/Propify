<?php

namespace App\Http\Resources\Requests\Auth\Auth;

use App\DTOs\Auth\RegisterUserDto;
use App\Enums\UserStatus;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) {
                    $user = User::where('email', $value)->first();
                    if ($user && $user->status !== UserStatus::Pending) {
                        $fail('Địa chỉ email này đã được đăng ký.');
                    }
                }
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
            // Note: `status` and `role` are intentionally missing to prevent mass assignment
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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.max' => 'Họ và tên không được vượt quá :max ký tự.',

            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
            'email.max' => 'Địa chỉ email không được vượt quá :max ký tự.',

            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ];
    }

    /**
     * Convert validated request data to a DTO.
     */
    public function toDto(): RegisterUserDto
    {
        return RegisterUserDto::fromRequest($this);
    }
}
