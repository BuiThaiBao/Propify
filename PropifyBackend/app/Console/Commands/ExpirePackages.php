<?php

namespace App\Console\Commands;

use App\Models\Listing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Gỡ cờ gói tin cho các listing đã hết hạn.
 *
 * Logic:
 *   WHERE package_id IS NOT NULL
 *     AND package_expires_at IS NOT NULL
 *     AND package_expires_at <= NOW()
 *   → SET package_id = NULL, package_expires_at = NULL
 */
final class ExpirePackages extends Command
{
    protected $signature = 'packages:expire';
    protected $description = 'Remove package from expired listings';

    public function handle(): int
    {
        $expiredListings = Listing::query()
            ->whereNotNull('package_id')
            ->whereNotNull('package_expires_at')
            ->where('package_expires_at', '<=', now())
            ->get(['id', 'package_id', 'package_expires_at', 'owner_id']);

        if ($expiredListings->isEmpty()) {
            $this->line('No expired packages found.');
            return self::SUCCESS;
        }

        $ids = $expiredListings->pluck('id')->toArray();

        // Batch update: gỡ cờ gói
        DB::table('listings')
            ->whereIn('id', $ids)
            ->update([
                'package_id'         => null,
                'package_expires_at' => null,
                'updated_at'         => now(),
            ]);

        // Clear cache
        try {
            Cache::tags(['listings:public'])->flush();
        } catch (\Throwable) {
            // Cache driver may not support tags
        }

        Log::info('[ExpirePackages] Expired packages removed', [
            'count'       => count($ids),
            'listing_ids' => $ids,
        ]);

        $this->info(sprintf('Expired %d listing packages.', count($ids)));

        return self::SUCCESS;
    }
}
