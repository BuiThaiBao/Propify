<?php

namespace App\Services\Payment\State;

use App\Models\Transaction;
use Closure;

/**
 * SUCCESS: đã thanh toán. Idempotent — callback lặp lại với thanh toán hợp lệ
 * vẫn coi là hoàn tất nhưng KHÔNG đổi dữ liệu / không nâng cấp lại.
 */
final class SuccessTransactionState implements TransactionState
{
    public function applyCallback(Transaction $transaction, bool $isPaid, bool $notExpired, Closure $onPaid): bool
    {
        return $isPaid;
    }
}
