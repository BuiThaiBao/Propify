<?php

namespace App\Services\Payment\Gateway;

/**
 * Kết quả callback đã được chuẩn hoá về dạng nội bộ (độc lập nhà cung cấp).
 * Adapter của từng cổng (VNPay, Momo...) chịu trách nhiệm map dữ liệu thô về đây.
 */
final class CallbackResult
{
    public function __construct(
        public readonly bool $isValidSignature,
        public readonly ?string $responseCode,
        public readonly ?string $transactionStatus,
        public readonly string $reference,
        /** @var array<string, mixed> Các cột cần lưu lại để đối soát (vnp_*). */
        public readonly array $gatewayFields,
    ) {}
}
