<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AttributeGroup extends Model
{
    protected $table = 'attribute_groups';

    protected $fillable = [
        'name',
        'code',
        'input_type',
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];

    // ==================== Relationships ====================

    /** Danh sách thuộc tính trong nhóm */
    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class, 'group_id');
    }
}
