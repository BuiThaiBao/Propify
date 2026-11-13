<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Models\Listing;
use App\Models\Transaction;
use App\Services\Listing\ListingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SepayIpnController
{
    public function __invoke(Request $request, ListingService $listingService): JsonResponse
    {
        $secret = (string) config('sepay.ipn_secret');
        if ($secret === '' || ! hash_equals($secret, (string) $request->header('X-Secret-Key'))) {
            return response()->json(['success' => false], $secret === '' ? 503 : 401);
        }

        $event = $request->json();
        if ($event->get('notification_type') !== 'ORDER_PAID') {
            return response()->json(['success' => true]);
        }

        $invoice = $request->input('order.order_invoice_number');
        $gatewayId = $request->input('transaction.transaction_id');
        $amount = $this->normalizeAmount($request->input('order.order_amount'));
        if (! is_string($invoice) || ! preg_match('/^SPG([1-9][0-9]{0,17})$/D', $invoice, $reference)
            || ! is_string($gatewayId) || $gatewayId === '' || strlen($gatewayId) > 100
            || $amount === null || $amount !== $this->normalizeAmount($request->input('transaction.transaction_amount'))
            || $request->input('order.order_status') !== 'CAPTURED'
            || $request->input('order.order_currency') !== 'VND'
            || $request->input('transaction.transaction_currency') !== 'VND'
            || $request->input('transaction.transaction_type') !== 'PAYMENT'
            || $request->input('transaction.transaction_status') !== 'APPROVED') {
            return response()->json(['success' => false], 422);
        }

        try {
            DB::transaction(function () use ($reference, $gatewayId, $amount, $listingService) {
                $transaction = Transaction::query()
                    ->where('payment_method', 'SEPAY_GATEWAY')
                    ->lockForUpdate()
                    ->find((int) $reference[1]);
                if (! $transaction || $transaction->status !== 'PENDING'
                    || ! $transaction->expires_at?->isFuture()
                    || $transaction->amount !== $amount
                    || Transaction::where('sepay_gateway_id', $gatewayId)->exists()) {
                    return;
                }

                Listing::query()->lockForUpdate()->findOrFail($transaction->listing_id);
                $transaction->forceFill(['sepay_gateway_id' => $gatewayId])->save();
                $listingService->completePaidUpgrade($transaction);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if (! Transaction::where('sepay_gateway_id', $gatewayId)->exists()) {
                throw $exception;
            }
        }

        return response()->json(['success' => true]);
    }

    private function normalizeAmount(mixed $amount): ?string
    {
        if ((! is_string($amount) && ! is_int($amount))
            || ! preg_match('/^([1-9][0-9]{0,11})(?:\.0{1,2})?$/D', (string) $amount, $matches)) {
            return null;
        }

        return $matches[1].'.00';
    }
}
