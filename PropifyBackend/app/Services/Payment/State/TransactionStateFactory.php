<?php

namespace App\Services\Payment\State;

use App\Models\Transaction;

/**
 * Chọn State theo trạng thái hiện tại của giao dịch.
 */
final class TransactionStateFactory
{
    public function for(Transaction $transaction): TransactionState
    {
        return match ($transaction->status) {
            'PENDING' => new PendingTransactionState,
            'SUCCESS' => new SuccessTransactionState,
            default => new SettledTransactionState,
        };
    }
}
