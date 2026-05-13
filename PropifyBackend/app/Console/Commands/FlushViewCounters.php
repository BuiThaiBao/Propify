<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class FlushViewCounters extends Command
{
    protected $signature = 'views:flush';
    protected $description = 'Flush Redis view counters to MySQL (batch update listings.views)';

    private const COUNTER_PREFIX = 'listing:views:';
    private const DIRTY_SET_KEY = 'listing:views:dirty';
    private const LOCK_TTL = 25;
    private const MAX_RETRIES = 3;
    private const CHUNK_SIZE = 100;

    public function handle(): int
    {
        $lock = Cache::lock('views_flush_lock', self::LOCK_TTL);

        if (!$lock->get()) {
            $this->line('Another flush is running. Skipping.');
            return self::SUCCESS;
        }

        try {
            $counters = $this->collectCounters();

            if ($counters === []) {
                $this->line('No pending view counters.');
                return self::SUCCESS;
            }

            $flushed = $this->flushToDatabase($counters);

            if ($flushed > 0) {
                $this->flushPublicListingsCache();
            }

            Log::info('ViewTracking: flush completed', [
                'listings_updated' => count($counters),
                'total_views' => array_sum($counters),
            ]);

            $this->info(sprintf(
                'Flushed %d counters (%d total views) to database.',
                count($counters),
                array_sum($counters),
            ));

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array<int, int> [listingId => viewCount]
     */
    private function collectCounters(): array
    {
        $listingIds = array_values(array_filter(array_map(
            static fn ($id) => (int) $id,
            Redis::smembers(self::DIRTY_SET_KEY) ?: [],
        )));

        if ($listingIds === []) {
            return [];
        }

        $luaScript = <<<'LUA'
local val = redis.call('GET', KEYS[1])
if val then
  redis.call('DEL', KEYS[1])
end
return val
LUA;

        $results = Redis::pipeline(function ($pipe) use ($listingIds, $luaScript) {
            foreach ($listingIds as $listingId) {
                $pipe->eval($luaScript, 1, self::COUNTER_PREFIX . $listingId);
            }
        });

        $counters = [];
        $completedIds = [];

        foreach ($listingIds as $index => $listingId) {
            $count = (int) ($results[$index] ?? 0);

            if ($count > 0) {
                $counters[$listingId] = $count;
                continue;
            }

            $completedIds[] = $listingId;
        }

        $this->removeDirtyIdsIfCountersAreEmpty($completedIds);

        return $counters;
    }

    private function flushToDatabase(array $counters): int
    {
        $flushed = 0;

        foreach (array_chunk($counters, self::CHUNK_SIZE, true) as $chunk) {
            if ($this->updateChunkWithRetry($chunk)) {
                $flushed += array_sum($chunk);
                $this->removeDirtyIdsIfCountersAreEmpty(array_keys($chunk));
            }
        }

        return $flushed;
    }

    private function updateChunkWithRetry(array $chunk): bool
    {
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                DB::transaction(function () use ($chunk) {
                    foreach ($chunk as $listingId => $views) {
                        DB::table('listings')
                            ->where('id', $listingId)
                            ->increment('views', $views);
                    }
                });

                return true;
            } catch (\Throwable $e) {
                Log::warning('ViewTracking: DB flush attempt failed', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'chunk' => array_keys($chunk),
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    usleep(500_000 * $attempt);
                }
            }
        }

        Log::error('ViewTracking: all DB retries failed, restoring to Redis', [
            'chunk' => $chunk,
        ]);

        $this->restoreToRedis($chunk);

        return false;
    }

    private function restoreToRedis(array $chunk): void
    {
        try {
            Redis::pipeline(function ($pipe) use ($chunk) {
                foreach ($chunk as $listingId => $views) {
                    $pipe->incrBy(self::COUNTER_PREFIX . $listingId, $views);
                    $pipe->sadd(self::DIRTY_SET_KEY, (string) $listingId);
                }
            });

            Log::info('ViewTracking: counters restored to Redis', [
                'count' => count($chunk),
            ]);
        } catch (\Throwable $e) {
            Log::critical('ViewTracking: CRITICAL - failed to restore counters to Redis', [
                'error' => $e->getMessage(),
                'lost' => $chunk,
            ]);
        }
    }

    private function removeDirtyIdsIfCountersAreEmpty(array $listingIds): void
    {
        $listingIds = array_values(array_filter(array_map('intval', $listingIds)));

        if ($listingIds === []) {
            return;
        }

        $luaScript = <<<'LUA'
for i, id in ipairs(ARGV) do
  local counterKey = KEYS[1] .. id
  if not redis.call('EXISTS', counterKey) then
    redis.call('SREM', KEYS[2], id)
  end
end
return 1
LUA;

        Redis::eval($luaScript, 2, self::COUNTER_PREFIX, self::DIRTY_SET_KEY, ...array_map('strval', $listingIds));
    }

    private function flushPublicListingsCache(): void
    {
        try {
            Cache::tags(['listings:public'])->flush();
        } catch (\Throwable $e) {
            Log::warning('ViewTracking: failed to flush listings:public cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
