<?php

namespace App\Models;

use App\Support\PropertySearchText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Property extends Model
{
    protected $table = 'properties';

    protected $fillable = [
        'type',
        'province_code',
        'province',
        'ward_code',
        'ward',
        'street_code',
        'project_name',
        'address_detail',
        'search_text',
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

    protected static function booted(): void
    {
        self::saving(function (Property $property): void {
            $property->search_text = PropertySearchText::build([
                $property->project_name,
                $property->province,
                $property->ward,
                $property->street_code,
                $property->address_detail,
            ]);
        });
    }

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

    /** Danh sach tien ich cua bat dong san */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'property_attributes', 'property_id', 'attribute_id')
            ->withPivot(['is_visible', 'display_order', 'is_featured'])
            ->whereHas('group', fn ($query) => $query->where('code', 'amenities'));
    }
}
