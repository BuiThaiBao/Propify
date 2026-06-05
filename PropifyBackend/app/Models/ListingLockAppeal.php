<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ListingLockAppeal extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_REVIEWED = 'REVIEWED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_RESOLVED = 'RESOLVED';

    protected $table = 'listing_lock_appeals';

    protected $fillable = [
        'listing_id',
        'user_id',
        'reason',
        'status',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
