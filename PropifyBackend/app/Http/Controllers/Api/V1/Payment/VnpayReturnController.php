<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Models\Transaction;
use App\Services\Listing\ListingService;
use App\Services\Payment\VnpayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class VnpayReturnController
{
    public function __construct(
        private readonly VnpayService $vnpayService,
        private readonly ListingService $listingService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $txnRef = (string) $request->query('vnp_TxnRef', '');
        $transactionId = $this->vnpayService->transactionIdFromTxnRef($txnRef);
        $transaction = $transactionId ? Transaction::find($transactionId) : null;
        $isValidHash = $this->vnpayService->isValidReturn($request);
        $isPaid = $isValidHash && $request->query('vnp_ResponseCode') === '00' && $request->query('vnp_TransactionStatus') === '00';
        $isCompleted = false;

        if ($transaction) {
            $transaction->update([
                'vnp_txn_ref' => $txnRef,
                'vnp_transaction_no' => $request->query('vnp_TransactionNo'),
                'vnp_bank_code' => $request->query('vnp_BankCode'),
                'vnp_response_code' => $request->query('vnp_ResponseCode'),
                'vnp_pay_date' => $request->query('vnp_PayDate'),
            ]);

            if ($isPaid && $transaction->status === 'PENDING' && (! $transaction->expires_at || $transaction->expires_at->isFuture())) {
                $this->listingService->completePaidUpgrade($transaction);
                $isCompleted = true;
            } elseif ($isPaid && $transaction->status === 'SUCCESS') {
                $isCompleted = true;
            } elseif ($isPaid && $transaction->status === 'PENDING') {
                $transaction->update(['status' => 'EXPIRED']);
            } elseif (! $isPaid && $transaction->status === 'PENDING') {
                $transaction->update(['status' => 'FAILED']);
            }
        }

        $status = $isCompleted ? 'success' : 'failed';
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $redirectUrl = $frontendUrl.'/payment/vnpay-result?status='.$status.'&transaction_id='.($transaction?->id ?? '');

        return $this->redirectResponse($isCompleted, $redirectUrl);
    }

    private function redirectResponse(bool $isCompleted, string $redirectUrl): Response
    {
        $title = $isCompleted ? 'Thanh toan VNPAY thanh cong' : 'Thanh toan VNPAY that bai';
        $message = $isCompleted
            ? 'Giao dich da duoc VNPAY xac nhan. Dang chuyen ve Propify...'
            : 'Giao dich chua thanh cong, da het han hoac khong hop le. Dang chuyen ve Propify...';
        $mark = $this->statusMark($isCompleted);
        $redirectUrlJson = json_encode($redirectUrl, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);

        $html = <<<HTML
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$title}</title>
  <style>
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; }
    .box { width: min(420px, calc(100vw - 32px)); background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px; text-align: center; box-shadow: 0 20px 50px rgba(15, 23, 42, .08); }
    .mark { width: 56px; height: 56px; border-radius: 999px; display: grid; place-items: center; margin: 0 auto 16px; color: #fff; font-size: 28px; background: #0ea5e9; }
    h1 { font-size: 22px; margin: 0 0 8px; }
    p { margin: 0; color: #475569; line-height: 1.5; }
  </style>
  <script>
    setTimeout(function () { window.location.href = {$redirectUrlJson}; }, 1500);
  </script>
</head>
<body>
  <main class="box">
    <div class="mark">{$mark}</div>
    <h1>{$title}</h1>
    <p>{$message}</p>
  </main>
</body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function statusMark(bool $isCompleted): string
    {
        return $isCompleted ? '&#10003;' : '!';
    }
}
