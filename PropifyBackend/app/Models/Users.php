<?php

namespace App\Models;

// ====================================================================
// BƯỚC 2: Model Users - Nền tảng của JWT Authentication
// ====================================================================
//
// GIẢI THÍCH:
// 1. Extend "Authenticatable" (KHÔNG PHẢI "Model"):
//    - Authenticatable = Model + các method auth (getAuthIdentifier, getAuthPassword...)
//    - Laravel Auth guard CẦN các method này để xác thực user
//    - Nếu extend Model thường → Auth::attempt() sẽ KHÔNG hoạt động!
//
// 2. Implement "JWTSubject":
//    - Interface từ tymon/jwt-auth, yêu cầu 2 method:
//    - getJWTIdentifier(): trả về giá trị unique của user (thường là id)
//      → Giá trị này sẽ nằm trong claim "sub" (subject) của JWT token
//    - getJWTCustomClaims(): thêm data tùy chỉnh vào token payload
//      → VD: role, email... (trả [] nếu không cần)
//
// LUỒNG: Khi JWTAuth::fromUser($user) được gọi:
//    1. Gọi $user->getJWTIdentifier() → lấy id (VD: 5)
//    2. Gọi $user->getJWTCustomClaims() → lấy custom claims
//    3. Tạo payload: { sub: 5, iat: 1234567890, exp: 1234571490, ... }
//    4. Ký payload bằng JWT_SECRET → tạo ra token string
// ====================================================================

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Users extends Authenticatable implements JWTSubject
{
    protected $table = 'users';

    protected $fillable = [
        'password',
        'full_name',
        'phone',
        'email',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'role' => 'string',
        'status' => 'string',
    ];

    // ==================== JWT Methods ====================

    /**
     * Trả về giá trị dùng làm "subject" claim trong JWT token.
     * Thường là primary key (id) của user.
     *
     * Khi decode token, JWT sẽ dùng giá trị này để tìm lại user từ DB.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // = $this->id
    }

    /**
     * Trả về các custom claims muốn thêm vào JWT token.
     * VD: return ['role' => $this->role];
     * Trả [] nếu không cần thêm gì.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    // ==================== Relationships ====================

    /** Danh sách bất động sản của user */
    public function properties()
    {
        return $this->hasMany(Properties::class, 'owner_id');
    }

    /** Danh sách lịch hẹn xem (user là người xem) */
    public function viewerAppointments()
    {
        return $this->hasMany(Appointments::class, 'viewer_id');
    }

    /** Danh sách lịch hẹn đăng (user là người đăng) */
    public function posterAppointments()
    {
        return $this->hasMany(Appointments::class, 'poster_id');
    }

    /** Danh sách giao dịch */
    public function transactions()
    {
        return $this->hasMany(Transactions::class, 'user_id');
    }

    /** Danh sách yêu thích */
    public function favorites()
    {
        return $this->hasMany(User_Favorites::class, 'user_id');
    }
}
