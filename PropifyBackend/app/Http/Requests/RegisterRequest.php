<?php

namespace App\Http\Requests;

// ====================================================================
// BƯỚC 4a: RegisterRequest - Validation cho đăng ký
// ====================================================================
//
// GIẢI THÍCH:
// Form Request tách logic validation ra khỏi Controller:
//   1. Client gửi POST /api/auth/register với body JSON
//   2. Laravel TỰ ĐỘNG gọi RegisterRequest trước khi vào Controller
//   3. Nếu validation FAIL → Laravel tự trả 422 (không vào Controller)
//   4. Nếu PASS → Controller nhận $request->validated() (chỉ chứa data hợp lệ)
//
// authorize() return true = ai cũng được gọi API này (public route)
// ====================================================================

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public route - ai cũng đăng ký được
    }

    public function rules(): array
    {
        return [
            'full_name'             => 'required|string|max:100',
            'phone'                 => 'required|string|max:20|unique:users,phone',
            'email'                 => 'required|email|max:100|unique:users,email',
            'password'              => 'required|string|min:6|confirmed',
            // 'confirmed' → yêu cầu field 'password_confirmation' phải khớp
            'status'                => 'nullable|in:A,IA,BAN',
        ];
    }
}
