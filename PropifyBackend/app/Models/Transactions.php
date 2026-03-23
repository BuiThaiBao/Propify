<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'listing_id',
        'package_id',
        'amount',
        'payment_method',
        'status',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'status' => 'string',
    ];

    // ==================== Relationships ====================

    /** User thực hiện giao dịch */
    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    /** Listing liên quan */
    public function listing()
    {
        return $this->belongsTo(Listings::class, 'listing_id');
    }

    /** Gói tin đã mua */
    public function package()
    {
        return $this->belongsTo(Packages::class, 'package_id');
    }
}
