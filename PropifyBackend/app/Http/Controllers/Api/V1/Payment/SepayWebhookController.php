<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Models\Listing;
use App\Models\Transaction;
use App\Services\Listing\ListingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SepayWebhookController
{
    public function __invoke(Request $request, ListingService $listingService): JsonResponse
    {
        $secret = (string) config('sepay.webhook_secret');
        $account = (string) config('sepay.test_account');
        $va = (string) config('sepay.test_va');
        if ($secret === '' || $account === '' || ! str_starts_with($va, 'SBSEPAY')) {
            return response()->json(['success' => false], 503);
        }

        $timestamp = (string) $request->header('X-SePay-Timestamp');
        $body = $request->getContent();
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);
        if (! ctype_digit($timestamp) || abs(now()->timestamp - (int) $timestamp) > 300
            || ! hash_equals($signature, (string) $request->header('X-SePay-Signature'))) {
            return response()->json(['success' => false], 401);
        }

        $event = json_decode($body, true);
        if (! is_array($event) || ! is_int($event['id'] ?? null) || $event['id'] <= 0
            || ! is_int($event['transferAmount'] ?? null) || $event['transferAmount'] <= 0
            || ! is_string($event['content'] ?? null)) {
            return response()->json(['success' => false], 422);
        }

        if (($event['transferType'] ?? null) !== 'in'
            || ($event['accountNumber'] ?? null) !== $account
            || ($event['subAccount'] ?? null) !== $va
            || preg_match('/^SPY([1-9][0-9]{0,17})$/D', trim($event['content']), $reference) !== 1) {
            return response()->json(['success' => true]);
        }

        try {
            DB::transaction(function () use ($event, $reference, $listingService) {
                $transaction = Transaction::query()
                    ->where('payment_method', 'SEPAY')
                    ->lockForUpdate()
                    ->find((int) $reference[1]);

                if (! $transaction || $transaction->status !== 'PENDING'
                    || ! $transaction->expires_at?->isFuture()
                    || $transaction->amount !== $event['transferAmount'].'.00'
                    || Transaction::where('sepay_event_id', $event['id'])->exists()) {
                    return;
                }

                Listing::query()->lockForUpdate()->findOrFail($transaction->listing_id);
                $transaction->forceFill(['sepay_event_id' => $event['id']])->save();
                $listingService->completePaidUpgrade($transaction);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if (! Transaction::where('sepay_event_id', $event['id'])->exists()) {
                throw $exception;
            }
        }

        return response()->json(['success' => true]);
    }
}
