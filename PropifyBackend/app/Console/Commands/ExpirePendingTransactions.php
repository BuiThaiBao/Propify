<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;

final class ExpirePendingTransactions extends Command
{
    protected $signature = 'transactions:expire-pending {--minutes=15 : Pending transaction age before expiration}';

    protected $description = 'Expire pending payment transactions that were not completed in time.';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $expired = Transaction::query()
            ->where('status', 'PENDING')
            ->where(function ($query) use ($minutes) {
                $query->where('expires_at', '<=', now())
                    ->orWhere(function ($query) use ($minutes) {
                        $query->whereNull('expires_at')
                            ->where('created_at', '<=', now()->subMinutes($minutes));
                    });
            })
            ->update([
                'status' => 'EXPIRED',
                'updated_at' => now(),
            ]);

        $this->info("Expired {$expired} pending transaction(s).");

        return self::SUCCESS;
    }
}
