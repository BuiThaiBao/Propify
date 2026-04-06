<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Listing extends Model
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
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /** Gói tin đã mua */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    /** Danh sách hình ảnh */
    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class, 'listing_id');
    }

    /** Danh sách lịch hẹn */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'listing_id');
    }

    /** Danh sách giao dịch */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'listing_id');
    }

    /** Danh sách yêu thích */
    public function favorites(): HasMany
    {
        return $this->hasMany(UserFavorite::class, 'listing_id');
    }
}
