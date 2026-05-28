<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ListingReport extends Model
{
    public const STATUS_WARNING = 'WARNING';

    protected $table = 'listing_reports';

    protected $fillable = [
        'listing_id',
        'reporter_id',
        'reason',
        'description',
        'status',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
