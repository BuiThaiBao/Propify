<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Users extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast to native types or enums.
     */
    protected $casts = [
        'role' => UserRole::class,
        'status' => UserStatus::class,
    ];

    // ==================== JWT Methods ====================

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role?->value,
            'status' => $this->status?->value,
            'id' => $this->id,
        ];
    }

    // ==================== Relationships ====================

    public function properties()
    {
        return $this->hasMany(Properties::class, 'owner_id');
    }

    public function viewerAppointments()
    {
        return $this->hasMany(Appointments::class, 'viewer_id');
    }

    public function posterAppointments()
    {
        return $this->hasMany(Appointments::class, 'poster_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transactions::class, 'user_id');
    }

    public function favorites()
    {
        return $this->hasMany(User_Favorites::class, 'user_id');
    }
}
