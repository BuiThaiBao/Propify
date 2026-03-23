<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Packages extends Model
{
    protected $table = 'packages';

    protected $fillable = [
        'name',
        'price',
        'duration_days',
        'priority_level',
        'features',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'priority_level' => 'integer',
        'features' => 'array',
    ];

    // ==================== Relationships ====================

    /** Danh sách listings sử dụng gói này */
    public function listings()
    {
        return $this->hasMany(Listings::class, 'package_id');
    }

    /** Danh sách giao dịch của gói này */
    public function transactions()
    {
        return $this->hasMany(Transactions::class, 'package_id');
    }
}
