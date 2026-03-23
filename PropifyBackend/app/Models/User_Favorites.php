<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User_Favorites extends Model
{
    protected $table = 'user_favorites';

    protected $fillable = [
        'user_id',
        'listing_id',
        'type',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    // ==================== Relationships ====================

    /** User yêu thích */
    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    /** Listing được yêu thích */
    public function listing()
    {
        return $this->belongsTo(Listings::class, 'listing_id');
    }
}
