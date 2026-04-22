<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AppointmentBooking extends Model
{
    protected $table = 'appointment_bookings';

    protected $fillable = [
        'slot_id',
        'viewer_id',
        'meet_time',
        'full_name',
        'phone_number',
        'email',
        'note',
        'is_deleted',
        'status',
        'confirm_deadline',
        'is_urgent',
    ];

    protected $casts = [
        'meet_time'        => 'datetime',
        'confirm_deadline' => 'datetime',
        'is_deleted'       => 'boolean',
        'is_urgent'        => 'boolean',
        'status'           => 'string',
    ];

    // ==================== Relationships ====================

    /** Slot lịch hẹn mà booking này thuộc về */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class, 'slot_id');
    }

    /** Người đặt lịch (khách xem nhà) */
    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }
}
