<?php

namespace App\Services\Payment;

use App\Models\Transaction;
use Illuminate\Http\Request;
use RuntimeException;

final class VnpayService
{
    public function createPaymentUrl(Transaction $transaction, string $clientIp): string
    {
        $tmnCode = (string) config('vnpay.tmn_code');
        $hashSecret = (string) config('vnpay.hash_secret');
        $paymentUrl = (string) config('vnpay.payment_url');

        if ($tmnCode === '' || $hashSecret === '' || $paymentUrl === '') {
            throw new RuntimeException('VNPAY configuration is missing.');
        }

        $txnRef = $this->txnRef($transaction);
        $amount = (int) round((float) $transaction->amount * 100);

        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => $amount,
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $txnRef,
            'vnp_OrderInfo' => 'Thanh toan goi tin Propify #'.$transaction->id,
            'vnp_OrderType' => 'billpayment',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => (string) config('vnpay.return_url'),
            'vnp_IpAddr' => $clientIp ?: '127.0.0.1',
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_ExpireDate' => ($transaction->expires_at ?? now()->addMinutes(15))->format('YmdHis'),
        ];

        $bankCode = (string) config('vnpay.bank_code');
        if ($bankCode !== '') {
            $params['vnp_BankCode'] = $bankCode;
        }

        ksort($params);
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC1738);
        $secureHash = hash_hmac('sha512', $query, $hashSecret);

        return $paymentUrl.'?'.$query.'&vnp_SecureHash='.$secureHash;
    }

    public function isValidReturn(Request $request): bool
    {
        $params = $request->query();
        $secureHash = (string) ($params['vnp_SecureHash'] ?? '');

        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
        ksort($params);

        $query = http_build_query($params, '', '&', PHP_QUERY_RFC1738);
        $expected = hash_hmac('sha512', $query, (string) config('vnpay.hash_secret'));

        return $secureHash !== '' && hash_equals($expected, $secureHash);
    }

    public function transactionIdFromTxnRef(string $txnRef): ?int
    {
        if (preg_match('/^PFY(\d+)$/', $txnRef, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function txnRef(Transaction $transaction): string
    {
        return 'PFY'.$transaction->id;
    }
}
