<?php

namespace App\Services\Payment;

/**
 * Kết quả xử lý callback trả về cho controller (đủ để dựng redirect).
 */
final class PaymentReturnResult
{
    public function __construct(
        public readonly bool $isCompleted,
        public readonly ?int $transactionId,
    ) {}
}
