<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AppointmentSlot extends Model
{
    protected $table = 'appointment_slots';

    protected $fillable = [
        'listing_id',
        'poster_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'start_time'  => 'string',
        'end_time'    => 'string',
        'is_active'   => 'boolean',
    ];

    // ==================== Relationships ====================

    /** Listing mà slot này thuộc về */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    /** Người đăng bài (chủ nhà / môi giới) */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'poster_id');
    }

    /** Danh sách booking của slot này */
    public function bookings(): HasMany
    {
        return $this->hasMany(AppointmentBooking::class, 'slot_id');
    }
}
