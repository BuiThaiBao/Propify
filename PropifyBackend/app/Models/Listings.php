<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listings extends Model
{
    protected $table = 'listings';

    protected $fillable = [
        'property_id',
        'title',
        'description',
        'ai_description',
        'status',
        'package_id',
        'score',
        'published_at',
        'expired_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    /** Bất động sản của listing */
    public function property()
    {
        return $this->belongsTo(Properties::class, 'property_id');
    }

    /** Gói tin đã mua */
    public function package()
    {
        return $this->belongsTo(Packages::class, 'package_id');
    }

    /** Danh sách hình ảnh */
    public function images()
    {
        return $this->hasMany(Listing_Images::class, 'listing_id');
    }

    /** Danh sách lịch hẹn */
    public function appointments()
    {
        return $this->hasMany(Appointments::class, 'listing_id');
    }

    /** Danh sách giao dịch */
    public function transactions()
    {
        return $this->hasMany(Transactions::class, 'listing_id');
    }

    /** Danh sách yêu thích */
    public function favorites()
    {
        return $this->hasMany(User_Favorites::class, 'listing_id');
    }
}
