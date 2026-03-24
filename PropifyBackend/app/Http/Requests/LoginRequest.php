<?php

namespace App\Http\Requests;

// ====================================================================
// BƯỚC 4b: LoginRequest - Validation cho đăng nhập
// ====================================================================
//
// GIẢI THÍCH:
// Tương tự RegisterRequest, validate trước khi vào Controller.
// Chỉ cần email + password là đủ.
// ====================================================================

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public route
    }

    public function rules(): array
    {
        return [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ];
    }
}
