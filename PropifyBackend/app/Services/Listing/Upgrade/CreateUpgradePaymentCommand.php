<?php

namespace App\Services\Listing\Upgrade;

use App\Jobs\ExpirePendingTransactionJob;
use App\Models\Listing;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class CreateUpgradePaymentCommand
{
    public function execute(
        User $user,
        Listing $listing,
        Package $newPackage,
        int $durationDays,
        float $amount
    ): array {
        $merchant = (string) config('sepay.merchant_id');
        $secret = (string) config('sepay.merchant_secret');
        $frontend = rtrim((string) config('app.frontend_url'), '/');
        if ($merchant === '' || $secret === '' || ! config('sepay.ipn_secret') || $frontend === '') {
            throw new ServiceUnavailableHttpException(null, 'Thiếu cấu hình Cổng thanh toán SePay Sandbox.');
        }
        abort_if($amount <= 0 || floor($amount) !== $amount, 422, 'Số tiền thanh toán phải là số nguyên dương.');

        $paymentExpiresAt = now()->addMinutes(15);
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'package_id' => $newPackage->id,
            'amount' => $amount,
            'duration_days' => $durationDays,
            'payment_method' => 'SEPAY_GATEWAY',
            'status' => 'PENDING',
            'transaction_date' => now(),
            'expires_at' => $paymentExpiresAt,
        ]);

        ExpirePendingTransactionJob::dispatch($transaction->id)->delay($paymentExpiresAt);

        $returnUrl = $frontend.'/payment/sepay-result?'.http_build_query([
            'listing_id' => $listing->id,
            'transaction_id' => $transaction->id,
        ]);
        // SePay verifies these fields in this order.
        $fields = [
            'order_amount' => (int) $amount,
            'merchant' => $merchant,
            'currency' => 'VND',
            'operation' => 'PURCHASE',
            'order_description' => 'Thanh toan goi tin Propify '.$transaction->id,
            'order_invoice_number' => 'SPG'.$transaction->id,
            'success_url' => $returnUrl.'&result=success',
            'error_url' => $returnUrl.'&result=error',
            'cancel_url' => $returnUrl.'&result=cancel',
        ];
        $parts = [];
        foreach ($fields as $name => $value) {
            $parts[] = $name.'='.$value;
        }
        $fields['signature'] = base64_encode(hash_hmac('sha256', implode(',', $parts), $secret, true));

        return ['checkout_url' => 'https://pay-sandbox.sepay.vn/v1/checkout/init', 'fields' => $fields];
    }
}
