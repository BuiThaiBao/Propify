<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Package extends Model
{
    protected $table = 'packages';

    protected $fillable = [
        'name',
        'slug',
        'price',
        'priority',
        'multiplier',
        'daily_quota',
        'decay_rate',
        'benefit_strategy_key',
        'badge',
        'color',
        'is_active',
    ];

    protected $casts = [
        'price'       => 'decimal:2',
        'priority'    => 'integer',
        'multiplier'  => 'float',
        'daily_quota' => 'integer',
        'decay_rate'  => 'float',
        'is_active'   => 'boolean',
    ];

    // ==================== Scopes ====================

    /** Chỉ lấy các gói đang active */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Sắp xếp theo priority giảm dần (gold trước) */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderByDesc('priority');
    }

    // ==================== Relationships ====================

    /** Danh sách listings sử dụng gói này */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'package_id');
    }

    /** Các option thời hạn + giá */
    public function pricings(): HasMany
    {
        return $this->hasMany(PackagePricing::class, 'package_id');
    }

    /** Danh sách giao dịch của gói này */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'package_id');
    }
}
