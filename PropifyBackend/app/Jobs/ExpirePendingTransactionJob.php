<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ExpirePendingTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $transactionId,
    ) {
    }

    public function handle(): void
    {
        $transaction = Transaction::find($this->transactionId);

        if (!$transaction || $transaction->status !== 'PENDING') {
            return;
        }

        if ($transaction->expires_at && $transaction->expires_at->isFuture()) {
            self::dispatch($transaction->id)->delay($transaction->expires_at);
            return;
        }

        $transaction->update(['status' => 'EXPIRED']);
    }
}
