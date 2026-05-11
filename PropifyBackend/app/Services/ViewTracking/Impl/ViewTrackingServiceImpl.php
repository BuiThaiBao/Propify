<?php

namespace App\Services\ViewTracking\Impl;

use App\Models\Listing;
use App\Services\ViewTracking\ViewTrackingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class ViewTrackingServiceImpl implements ViewTrackingService
{
    /** Redis key prefix cho dedup */
    private const DEDUP_PREFIX = 'view:listing:';

    /** Redis key prefix cho atomic counter */
    private const COUNTER_PREFIX = 'listing:views:';

    /** Dedup TTL: 30 phút (1800 giây) */
    private const DEDUP_TTL = 1800;

    /** Common bot User-Agent patterns */
    private const BOT_PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp', 'mediapartners',
        'facebookexternalhit', 'linkedinbot', 'twitterbot',
        'whatsapp', 'telegrambot', 'baiduspider', 'yandexbot',
        'duckduckbot', 'sogou', 'exabot', 'ia_archiver',
        'semrush', 'ahref', 'mj12bot', 'dotbot', 'petalbot',
        'headlesschrome', 'phantomjs', 'selenium', 'puppeteer',
        'wget', 'curl', 'httpie', 'python-requests', 'go-http',
        'java/', 'apache-httpclient', 'okhttp',
    ];

    public function trackView(
        int     $listingId,
        ?int    $userId,
        string  $ip,
        string  $userAgent,
        ?string $anonCookie = null,
    ): array {
        // 1. Bot / suspicious detection
        if ($this->isSuspicious($userAgent)) {
            Log::debug('ViewTracking: bot/suspicious rejected', [
                'listing_id' => $listingId,
                'ip'         => $ip,
                'ua'         => mb_substr($userAgent, 0, 100),
            ]);

            return ['counted' => false, 'reason' => 'bot_detected'];
        }

        // 2. Validate listing exists & is ACTIVE
        $exists = Listing::where('id', $listingId)
            ->where('status', 'ACTIVE')
            ->exists();

        if (!$exists) {
            return ['counted' => false, 'reason' => 'invalid_listing'];
        }

        // 3. Build dedup key
        $dedupKey = $this->buildDedupKey($listingId, $userId, $ip, $userAgent, $anonCookie);

        // 4. Atomic dedup check: SET NX EX (chỉ set nếu chưa tồn tại)
        //    Returns true nếu key mới được set (= view mới), false nếu đã tồn tại
        $isNew = Redis::set($dedupKey, '1', 'EX', self::DEDUP_TTL, 'NX');

        if (!$isNew) {
            return ['counted' => false, 'reason' => 'duplicated_view'];
        }

        // 5. Atomic increment counter
        $counterKey = self::COUNTER_PREFIX . $listingId;
        Redis::incr($counterKey);

        Log::debug('ViewTracking: view counted', [
            'listing_id' => $listingId,
            'user_id'    => $userId,
            'ip'         => $ip,
        ]);

        return ['counted' => true, 'reason' => 'view_counted'];
    }

    /**
     * Build Redis dedup key.
     *
     * Logged user:  view:listing:{id}:user:{userId}
     * Guest:        view:listing:{id}:guest:{sha256(ip + ua + anonCookie)}
     *
     * Thêm anonCookie để giảm collision khi nhiều user cùng IP (NAT, trường học, công ty).
     */
    private function buildDedupKey(
        int     $listingId,
        ?int    $userId,
        string  $ip,
        string  $userAgent,
        ?string $anonCookie,
    ): string {
        if ($userId !== null) {
            return self::DEDUP_PREFIX . "{$listingId}:user:{$userId}";
        }

        // Guest fingerprint: ip + ua + anonCookie để giảm NAT collision
        $fingerprint = hash('sha256', $ip . '|' . $userAgent . '|' . ($anonCookie ?? ''));

        return self::DEDUP_PREFIX . "{$listingId}:guest:{$fingerprint}";
    }

    /**
     * Detect bot/crawler/suspicious requests.
     *
     * Không reject cứng khi empty UA — chỉ mark suspicious.
     * Một số mobile webview và privacy tools có thể thiếu UA.
     */
    private function isSuspicious(string $userAgent): bool
    {
        // Empty UA: cho qua nhưng log (không reject cứng)
        if (trim($userAgent) === '') {
            Log::info('ViewTracking: empty User-Agent detected (allowing)');
            return false;
        }

        $uaLower = mb_strtolower($userAgent, 'UTF-8');

        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($uaLower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
