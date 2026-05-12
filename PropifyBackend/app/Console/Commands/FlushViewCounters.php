<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Batch flush Redis view counters → MySQL.
 *
 * Architecture:
 *   Redis INCR (per request) → This command (scheduled) → MySQL batch UPDATE
 *
 * Production concerns addressed:
 *   - SCAN instead of KEYS (non-blocking)
 *   - Lua script for atomic GET+DEL (Redis < 6.2 safe)
 *   - Distributed lock (prevents double flush)
 *   - Pipeline for batch Redis reads (reduces roundtrips)
 *   - Retry + restore on DB failure
 *   - Chunked DB updates (prevents deadlock)
 */
final class FlushViewCounters extends Command
{
    protected $signature = 'views:flush';
    protected $description = 'Flush Redis view counters to MySQL (batch update listings.views)';

    /** Redis key prefix for counters */
    private const COUNTER_PREFIX = 'listing:views:';

    /** Lock timeout (giây) — phải < scheduler interval */
    private const LOCK_TTL = 25;

    /** Max retry khi DB fail */
    private const MAX_RETRIES = 3;

    /** Chunk size cho DB batch update */
    private const CHUNK_SIZE = 100;

    public function handle(): int
    {
        // ── Distributed lock: tránh 2 scheduler chạy đồng thời ──
        $lock = Cache::lock('views_flush_lock', self::LOCK_TTL);

        if (!$lock->get()) {
            $this->line('Another flush is running. Skipping.');
            return self::SUCCESS;
        }

        try {
            $counters = $this->collectCounters();

            if (empty($counters)) {
                $this->line('No pending view counters.');
                return self::SUCCESS;
            }

            $this->flushToDatabase($counters);

            // Xoá cache của trang chủ/danh sách tin đăng để view mới hiển thị ngay lập tức
            try {
                Cache::tags(['listings:public'])->flush();
            } catch (\Throwable $e) {
                Log::warning('ViewTracking: failed to flush listings:public cache', ['error' => $e->getMessage()]);
            }

            Log::info('ViewTracking: flush completed', [
                'listings_updated' => count($counters),
                'total_views'      => array_sum($counters),
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
     * Collect all pending counters from Redis using SCAN (non-blocking).
     *
     * Dùng Lua script cho atomic GET+DEL thay vì GETDEL (Redis >= 6.2 only).
     *
     * @return array<int, int>  [listingId => viewCount]
     */
    private function collectCounters(): array
    {
        $counters = [];
        $prefix = config('database.redis.options.prefix', '');
        $pattern = $prefix . self::COUNTER_PREFIX . '*';

        // Lua script: atomic GET + DEL — safe cho mọi Redis version
        $luaScript = "local val = redis.call('GET', KEYS[1]); if val then redis.call('DEL', KEYS[1]); end; return val;";

        // Dùng SCAN cursor-based (KHÔNG dùng KEYS — block Redis thread)
        $cursor = '0';
        do {
            // scan trả về [nextCursor, [key1, key2, ...]]
            [$cursor, $keys] = Redis::scan($cursor, [
                'match' => $pattern,
                'count' => 200,
            ]);

            // Redis::scan có thể trả null cho keys
            if (empty($keys)) {
                continue;
            }

            // Pipeline để giảm roundtrip khi có nhiều keys
            $results = Redis::pipeline(function ($pipe) use ($keys, $luaScript) {
                foreach ($keys as $fullKey) {
                    // Cần strip prefix vì Redis::eval dùng raw key
                    $rawKey = $this->stripPrefix($fullKey);
                    $pipe->eval($luaScript, 1, $rawKey);
                }
            });

            foreach ($keys as $index => $fullKey) {
                $rawKey = $this->stripPrefix($fullKey);
                $listingId = (int) str_replace(self::COUNTER_PREFIX, '', $rawKey);
                $count = (int) ($results[$index] ?? 0);

                if ($count > 0 && $listingId > 0) {
                    $counters[$listingId] = ($counters[$listingId] ?? 0) + $count;
                }
            }
        } while ($cursor !== '0' && $cursor !== 0 && $cursor !== false);

        return $counters;
    }

    /**
     * Batch update MySQL với retry + restore on failure.
     *
     * @param array<int, int> $counters [listingId => viewCount]
     */
    private function flushToDatabase(array $counters): void
    {
        // Chunk để tránh deadlock trên MySQL
        $chunks = array_chunk($counters, self::CHUNK_SIZE, true);

        foreach ($chunks as $chunk) {
            $this->updateChunkWithRetry($chunk);
        }
    }

    /**
     * Update một chunk với retry logic.
     * Nếu 3 lần đều fail → restore counter về Redis để không mất data.
     */
    private function updateChunkWithRetry(array $chunk): void
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

                return; // Success
            } catch (\Throwable $e) {
                Log::warning('ViewTracking: DB flush attempt failed', [
                    'attempt' => $attempt,
                    'error'   => $e->getMessage(),
                    'chunk'   => array_keys($chunk),
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    usleep(500_000 * $attempt); // Exponential backoff: 500ms, 1s, 1.5s
                }
            }
        }

        // Tất cả retries đều fail → restore về Redis để không mất views
        Log::error('ViewTracking: all DB retries failed, restoring to Redis', [
            'chunk' => $chunk,
        ]);

        $this->restoreToRedis($chunk);
    }

    /**
     * Restore counters về Redis khi DB flush fail.
     * Ensures eventual consistency — views sẽ được flush lần sau.
     */
    private function restoreToRedis(array $chunk): void
    {
        try {
            Redis::pipeline(function ($pipe) use ($chunk) {
                foreach ($chunk as $listingId => $views) {
                    $pipe->incrBy(self::COUNTER_PREFIX . $listingId, $views);
                }
            });

            Log::info('ViewTracking: counters restored to Redis', [
                'count' => count($chunk),
            ]);
        } catch (\Throwable $e) {
            // Worst case: cả DB và Redis đều fail → log + alert
            Log::critical('ViewTracking: CRITICAL — failed to restore counters to Redis', [
                'error' => $e->getMessage(),
                'lost'  => $chunk,
            ]);
        }
    }

    /**
     * Strip Redis prefix from key.
     * Redis driver tự thêm prefix, nhưng SCAN trả về full key.
     */
    private function stripPrefix(string $key): string
    {
        $prefix = config('database.redis.options.prefix', '');

        if ($prefix && str_starts_with($key, $prefix)) {
            return substr($key, strlen($prefix));
        }

        return $key;
    }
}
