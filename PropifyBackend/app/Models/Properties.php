<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Properties extends Model
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
    public function owner()
    {
        return $this->belongsTo(Users::class, 'owner_id');
    }

    /** Danh sách listings của bất động sản */
    public function listings()
    {
        return $this->hasMany(Listings::class, 'property_id');
    }

    /** Danh sách thuộc tính (many-to-many qua bảng property_attributes) */
    public function attributes()
    {
        return $this->belongsToMany(Attributes::class, 'property_attributes', 'property_id', 'attribute_id');
    }
}
