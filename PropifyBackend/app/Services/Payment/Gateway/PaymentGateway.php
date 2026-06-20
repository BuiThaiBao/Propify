<?php

namespace App\Services\Payment\Gateway;

use App\Models\Transaction;
use Illuminate\Http\Request;

/**
 * Cổng thanh toán nội bộ (Adapter). Use case chỉ phụ thuộc interface này,
 * không phụ thuộc trực tiếp VNPay/Momo... Thêm cổng mới = thêm một Adapter.
 */
interface PaymentGateway
{
    /** Mã phương thức ('VNPAY', ...). */
    public function method(): string;

    /** Tạo URL chuyển hướng sang cổng thanh toán cho một giao dịch. */
    public function createPaymentUrl(Transaction $transaction, string $clientIp): string;

    /** Chuẩn hoá dữ liệu callback từ cổng về CallbackResult nội bộ. */
    public function verifyCallback(Request $request): CallbackResult;

    /** Tách id giao dịch nội bộ từ mã tham chiếu của cổng. */
    public function transactionIdFromReference(string $reference): ?int;
}
