<?php

namespace App\Services\Payment\Gateway;

use RuntimeException;

/**
 * Chọn cổng thanh toán theo phương thức (Factory). Thêm cổng mới chỉ cần
 * thêm một nhánh + một Adapter, không sửa use case.
 */
final class PaymentProviderFactory
{
    public function __construct(
        private readonly VnpayGateway $vnpayGateway,
    ) {}

    public function for(string $method): PaymentGateway
    {
        return match (strtoupper($method)) {
            'VNPAY' => $this->vnpayGateway,
            default => throw new RuntimeException("Unsupported payment method: {$method}"),
        };
    }
}
