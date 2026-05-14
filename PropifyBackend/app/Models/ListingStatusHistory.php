<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ListingStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'listing_status_histories';

    protected $fillable = [
        'user_id',
        'listing_id',
        'action',
        'reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }
}
