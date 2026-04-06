<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Property extends Model
{
    protected $table = 'properties';

    protected $fillable = [
        'owner_id',
        'type',
        'province_code',
        'district_code',
        'address_detail',
        'area',
        'price',
        'bedrooms',
        'bathrooms',
        'lat',
        'lng',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'price' => 'decimal:2',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
    ];

    // ==================== Relationships ====================

    /** Chủ sở hữu bất động sản */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** Danh sách listings của bất động sản */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'property_id');
    }

    /** Danh sách thuộc tính (many-to-many qua bảng property_attributes) */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'property_attributes', 'property_id', 'attribute_id');
    }
}
