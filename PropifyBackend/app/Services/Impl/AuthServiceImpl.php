<?php

namespace App\Services\Impl;

// ====================================================================
// BƯỚC 5b: AuthServiceImpl - Nơi chứa TOÀN BỘ logic JWT
// ====================================================================
//
// GIẢI THÍCH TỪNG METHOD:
//
// ┌──────────────────────────────────────────────────────────────────┐
// │ register($data):                                                │
// │   1. Tạo user mới trong DB (hash password trước khi lưu)       │
// │   2. JWTAuth::fromUser($user) → tạo JWT token cho user mới     │
// │      → Gọi $user->getJWTIdentifier() lấy id                   │
// │      → Tạo payload {sub: id, iat: now, exp: now+TTL}           │
// │      → Ký bằng JWT_SECRET → trả về token string                │
// │   3. Trả về {user, token}                                      │
// └──────────────────────────────────────────────────────────────────┘
//
// ┌──────────────────────────────────────────────────────────────────┐
// │ login($credentials):                                            │
// │   1. Auth::attempt(['email'=>..., 'password'=>...])             │
// │      → Guard 'api' (JWT) xử lý:                                │
// │        a. Tìm user bằng email                                  │
// │        b. Hash::check(input_password, user.password)            │
// │        c. Nếu đúng → tạo JWT token, trả về token string        │
// │        d. Nếu sai → trả về false                               │
// └──────────────────────────────────────────────────────────────────┘
//
// ┌──────────────────────────────────────────────────────────────────┐
// │ logout():                                                       │
// │   Auth::logout() → JWT Guard invalidate token hiện tại          │
// │   → Đưa token vào blacklist (nếu blacklist_enabled = true)      │
// │   → Token cũ không dùng được nữa                               │
// └──────────────────────────────────────────────────────────────────┘
//
// ┌──────────────────────────────────────────────────────────────────┐
// │ refresh():                                                      │
// │   Auth::refresh() → Tạo token MỚI từ token CŨ                  │
// │   → Token cũ bị invalidate (đưa vào blacklist)                 │
// │   → Token mới có exp mới (reset thời gian hết hạn)             │
// └──────────────────────────────────────────────────────────────────┘
//
// ┌──────────────────────────────────────────────────────────────────┐
// │ me():                                                           │
// │   Auth::user() → JWT Guard đọc token từ request header          │
// │   → Giải mã token → lấy claim "sub" (user id)                  │
// │   → Users::find(id) → trả về user object                       │
// └──────────────────────────────────────────────────────────────────┘
// ====================================================================

use App\Models\Users;
use App\Services\AuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthServiceImpl implements AuthService
{
    /**
     * Đăng nhập: kiểm tra email + password → trả về JWT token
     */
    public function login(array $credential)
    {
        // Auth::attempt() dùng JWT guard (vì default guard = 'api')
        // → Tìm user bằng email, check password, tạo token
        $token = Auth::attempt($credential);

        if (!$token) {
            return null; // Email hoặc password sai
        }

        return $token; // JWT token string
    }

    /**
     * Đăng ký: tạo user mới + tự động tạo JWT token
     */
    public function register(array $data)
    {
        // Tạo user với password đã hash
        $user = Users::create([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'USER',
            'status' => $data['status'] ?? 'A',
        ]);

        // Tạo JWT token cho user vừa đăng ký (không cần login lại)
        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Đăng xuất: invalidate token hiện tại
     */
    public function logout()
    {
        Auth::logout(); // Token bị đưa vào blacklist
    }

    /**
     * Refresh: tạo token mới từ token cũ (token cũ bị hủy)
     */
    public function refresh()
    {
        return Auth::refresh();
    }

    /**
     * Lấy thông tin user đang đăng nhập (từ token)
     */
    public function me()
    {
        return Auth::user();
    }
}
