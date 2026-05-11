<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

final class PackagePricing extends Model
{
    protected $table = 'package_pricings';

    protected $fillable = [
        'package_id',
        'duration_days',
        'price',
        'label',
        'is_active',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'price'         => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    // ==================== Relationships ====================

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    // ==================== Scopes ====================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
