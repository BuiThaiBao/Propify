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
        'owner_id',
        'demand_type',
        'title',
        'description',
        'ai_description',
        'status',
        'package_id',
        'score',
        'is_verified',
        'has_video',
        'request_verification',
        'appointment_at',
        'appointment_days',
        'appointment_time_slot',
        'appointment_contact_name',
        'appointment_contact_phone',
        'appointment_contact_email',
        'appointment_note',
        'rejection_reason',
        'submitted_at',
        'published_at',
        'expired_at',
        'views',
        'package_expires_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'views' => 'integer',
        'is_verified' => 'boolean',
        'has_video' => 'boolean',
        'request_verification' => 'boolean',
        'appointment_at' => 'datetime',
        'appointment_days' => 'array',
        'submitted_at' => 'datetime',
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
        'package_expires_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    /** Bất động sản của listing */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /** Chủ sở hữu listing */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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

    public function videos(): HasMany
    {
        return $this->hasMany(ListingVideo::class, 'listing_id');
    }

    public function verificationDocuments(): HasMany
    {
        return $this->hasMany(ListingVerificationDocument::class, 'listing_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'listing_id');
    }

    /** Danh sách slot lịch hẹn */
    public function appointmentSlots(): HasMany
    {
        return $this->hasMany(AppointmentSlot::class, 'listing_id');
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
