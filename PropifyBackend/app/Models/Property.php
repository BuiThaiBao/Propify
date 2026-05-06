<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Property extends Model
{
    protected $table = 'properties';

    protected $fillable = [
        'type',
        'province_code',
        'ward_code',
        'street_code',
        'project_name',
        'address_detail',
        'area',
        'price',
        'is_negotiable',
        'bedrooms',
        'bathrooms',
        'floors',
        'floor_number',
        'balconies',
        'facade_width',
        'depth',
        'road_width',
        'direction_code',
        'balcony_direction_code',
        'furniture_status',
        'contact_name',
        'contact_phone',
        'contact_email',
        'poster_type',
        'amenities',
        'legal_paper_types',
        'public_info_agreed',
        'meta',
        'lat',
        'lng',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'price' => 'decimal:2',
        'is_negotiable' => 'boolean',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'floors' => 'integer',
        'floor_number' => 'integer',
        'balconies' => 'integer',
        'facade_width' => 'decimal:2',
        'depth' => 'decimal:2',
        'road_width' => 'decimal:2',
        'amenities' => 'array',
        'legal_paper_types' => 'array',
        'public_info_agreed' => 'boolean',
        'meta' => 'array',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
    ];

    // ==================== Relationships ====================

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
