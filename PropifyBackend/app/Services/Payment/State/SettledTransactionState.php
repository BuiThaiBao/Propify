<?php

namespace App\Services\Payment\State;

use App\Models\Transaction;
use Closure;

/**
 * Trạng thái đã chốt (FAILED / EXPIRED): callback không gây thay đổi gì và
 * không bao giờ được coi là hoàn tất.
 */
final class SettledTransactionState implements TransactionState
{
    public function applyCallback(Transaction $transaction, bool $isPaid, bool $notExpired, Closure $onPaid): bool
    {
        return false;
    }
}
