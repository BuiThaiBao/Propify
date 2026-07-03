<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Attribute extends Model
{
    protected $table = 'attributes';

    protected $fillable = [
        'group_id',
        'name',
        'description',
        'icon',
        'order_index',
        'is_active',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'is_active' => 'boolean',
    ];

    // ==================== Relationships ====================

    /** Nhóm thuộc tính */
    public function group(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class, 'group_id');
    }

    /** Danh sách bất động sản có thuộc tính này (many-to-many) */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_attributes', 'attribute_id', 'property_id')
            ->withPivot(['is_visible', 'display_order', 'is_featured']);
    }
}
