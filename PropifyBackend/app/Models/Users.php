<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
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
