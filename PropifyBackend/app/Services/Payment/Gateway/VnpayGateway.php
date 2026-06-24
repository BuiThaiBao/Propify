<?php

namespace App\Services\Payment\Gateway;

use App\Models\Transaction;
use App\Services\Payment\VnpayService;
use Illuminate\Http\Request;

/**
 * Adapter cho cổng VNPay: bọc VnpayService (ký HMAC, dựng URL, kiểm chữ ký)
 * và map dữ liệu callback thô (vnp_*) về CallbackResult nội bộ.
 */
final class VnpayGateway implements PaymentGateway
{
    public function __construct(
        private readonly VnpayService $vnpayService,
    ) {}

    public function method(): string
    {
        return 'VNPAY';
    }

    public function createPaymentUrl(Transaction $transaction, string $clientIp): string
    {
        return $this->vnpayService->createPaymentUrl($transaction, $clientIp);
    }

    public function verifyCallback(Request $request): CallbackResult
    {
        $reference = (string) $request->query('vnp_TxnRef', '');

        return new CallbackResult(
            isValidSignature: $this->vnpayService->isValidReturn($request),
            responseCode: $request->query('vnp_ResponseCode'),
            transactionStatus: $request->query('vnp_TransactionStatus'),
            reference: $reference,
            gatewayFields: [
                'vnp_txn_ref' => $reference,
                'vnp_transaction_no' => $request->query('vnp_TransactionNo'),
                'vnp_bank_code' => $request->query('vnp_BankCode'),
                'vnp_response_code' => $request->query('vnp_ResponseCode'),
                'vnp_pay_date' => $request->query('vnp_PayDate'),
            ],
        );
    }

    public function transactionIdFromReference(string $reference): ?int
    {
        return $this->vnpayService->transactionIdFromTxnRef($reference);
    }
}
