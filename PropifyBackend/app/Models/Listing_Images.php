<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listing_Images extends Model
{
    protected $table = 'listing_images';

    protected $fillable = [
        'listing_id',
        'image_url',
        'is_thumbnail',
    ];

    protected $casts = [
        'is_thumbnail' => 'boolean',
    ];

    // ==================== Relationships ====================

    /** Listing chứa hình ảnh */
    public function listing()
    {
        return $this->belongsTo(Listings::class, 'listing_id');
    }
}
