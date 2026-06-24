<?php

namespace App\Services\Payment\State;

use App\Models\Transaction;
use Closure;

/**
 * PENDING: chờ thanh toán. Giữ nguyên logic gốc của VnpayReturnController:
 *  - thanh toán hợp lệ & còn hạn → hoàn tất nâng cấp (onPaid tự đặt SUCCESS), completed.
 *  - thanh toán hợp lệ nhưng đã hết hạn → EXPIRED, chưa completed.
 *  - thanh toán không hợp lệ → FAILED, chưa completed.
 */
final class PendingTransactionState implements TransactionState
{
    public function applyCallback(Transaction $transaction, bool $isPaid, bool $notExpired, Closure $onPaid): bool
    {
        if ($isPaid && $notExpired) {
            $onPaid($transaction);

            return true;
        }

        if ($isPaid) {
            $transaction->update(['status' => 'EXPIRED']);

            return false;
        }

        $transaction->update(['status' => 'FAILED']);

        return false;
    }
}
