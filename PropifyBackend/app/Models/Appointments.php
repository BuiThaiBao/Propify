<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointments extends Model
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
    public function listing()
    {
        return $this->belongsTo(Listings::class, 'listing_id');
    }

    /** Người xem */
    public function viewer()
    {
        return $this->belongsTo(Users::class, 'viewer_id');
    }

    /** Người đăng */
    public function poster()
    {
        return $this->belongsTo(Users::class, 'poster_id');
    }
}
