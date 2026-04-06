<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Appointment extends Model
{
    protected $table = 'appointments';

    protected $fillable = [
        'listing_id',
        'viewer_id',
        'poster_id',
        'meet_time',
        'note',
        'status',
    ];

    protected $casts = [
        'meet_time' => 'datetime',
        'status' => 'string',
    ];

    // ==================== Relationships ====================

    /** Listing được hẹn xem */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    /** Người xem */
    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    /** Người đăng */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'poster_id');
    }
}
