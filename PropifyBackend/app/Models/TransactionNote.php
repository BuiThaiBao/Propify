<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TransactionNote extends Model
{
    protected $table = 'transaction_notes';

    // Không dùng timestamps mặc định vì bảng chỉ có created_at
    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'admin_id',
        'note',
    ];

    protected $casts = [
        'transaction_id' => 'integer',
        'admin_id' => 'integer',
        'created_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    /** Giao dịch liên quan */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /** Admin thực hiện ghi chú */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
