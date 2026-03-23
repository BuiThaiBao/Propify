<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attributes extends Model
{
    protected $table = 'attributes';

    protected $fillable = [
        'group_id',
        'name',
        'icon',
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];

    // ==================== Relationships ====================

    /** Nhóm thuộc tính */
    public function group()
    {
        return $this->belongsTo(Attribute_Groups::class, 'group_id');
    }

    /** Danh sách bất động sản có thuộc tính này (many-to-many) */
    public function properties()
    {
        return $this->belongsToMany(Properties::class, 'property_attributes', 'attribute_id', 'property_id');
    }
}
