<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute_Groups extends Model
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
    public function attributes()
    {
        return $this->hasMany(Attributes::class, 'group_id');
    }
}
