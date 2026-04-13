<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

final class User extends Authenticatable implements JWTSubject
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
        'google_id',
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
        'password' => 'hashed',
    ];

    // ==================== JWT Methods ====================

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role?->value,
            'status' => $this->status?->value,
            'id' => $this->id,
        ];
    }

    // ==================== Relationships ====================

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    /** Các slot lịch hẹn mà user đã tạo (với tư cách người đăng) */
    public function appointmentSlots(): HasMany
    {
        return $this->hasMany(AppointmentSlot::class, 'poster_id');
    }

    /** Các booking mà user đã đặt (với tư cách người xem) */
    public function appointmentBookings(): HasMany
    {
        return $this->hasMany(AppointmentBooking::class, 'viewer_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(UserFavorite::class, 'user_id');
    }
}
