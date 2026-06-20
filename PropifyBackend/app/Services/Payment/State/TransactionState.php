<?php

namespace App\Services\Payment\State;

use App\Models\Transaction;
use Closure;

/**
 * Trạng thái giao dịch khi xử lý callback (State pattern). Mỗi trạng thái tự
 * quyết định cách phản ứng với kết quả callback và trả về "đã hoàn tất hay chưa"
 * (dùng cho việc chuyển hướng/hiển thị).
 */
interface TransactionState
{
    /**
     * @param  bool  $isPaid  Callback xác nhận đã thanh toán hợp lệ.
     * @param  bool  $notExpired  Giao dịch còn hạn (chưa hết hạn).
     * @param  Closure  $onPaid  Hành động hoàn tất nâng cấp khi thanh toán thành công.
     * @return bool Giao dịch có được coi là hoàn tất không.
     */
    public function applyCallback(Transaction $transaction, bool $isPaid, bool $notExpired, Closure $onPaid): bool;
}
