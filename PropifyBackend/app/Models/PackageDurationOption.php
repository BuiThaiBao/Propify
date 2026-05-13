<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class PackageDurationOption extends Model
{
    protected $table = 'package_duration_options';

    protected $fillable = [
        'days',
        'label',
        'is_active',
    ];

    protected $casts = [
        'days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
