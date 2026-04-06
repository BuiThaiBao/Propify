<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Package extends Model
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
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'package_id');
    }

    /** Danh sách giao dịch của gói này */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'package_id');
    }
}
