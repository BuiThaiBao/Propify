<?php

namespace App\Services\Payment;

use App\Models\Transaction;
use App\Services\Listing\ListingService;
use App\Services\Payment\Gateway\PaymentProviderFactory;
use App\Services\Payment\State\TransactionStateFactory;
use Illuminate\Http\Request;

/**
 * Service điều phối luồng callback thanh toán: chuẩn hoá callback (Adapter),
 * tìm giao dịch, lưu dữ liệu đối soát, xác định đã thanh toán hay chưa, rồi để
 * State của giao dịch quyết định kết quả. Controller chỉ dựng trang chuyển hướng.
 */
final class PaymentReturnProcessor
{
    public function __construct(
        private readonly PaymentProviderFactory $paymentProviderFactory,
        private readonly TransactionStateFactory $stateFactory,
        private readonly ListingService $listingService,
    ) {}

    public function handleReturn(Request $request): PaymentReturnResult
    {
        $gateway = $this->paymentProviderFactory->for('VNPAY');
        $result = $gateway->verifyCallback($request);

        $transactionId = $gateway->transactionIdFromReference($result->reference);
        $transaction = $transactionId ? Transaction::find($transactionId) : null;

        $isCompleted = false;

        if ($transaction) {
            $transaction->update($result->gatewayFields);

            $isPaid = $result->isValidSignature
                && $result->responseCode === '00'
                && $result->transactionStatus === '00';
            $notExpired = ! $transaction->expires_at || $transaction->expires_at->isFuture();

            $isCompleted = $this->stateFactory->for($transaction)->applyCallback(
                $transaction,
                $isPaid,
                $notExpired,
                fn (Transaction $t) => $this->listingService->completePaidUpgrade($t),
            );
        }

        return new PaymentReturnResult($isCompleted, $transaction?->id);
    }
}
