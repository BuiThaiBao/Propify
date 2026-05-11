<?php

namespace App\Services\ViewTracking;

interface ViewTrackingService
{
    /**
     * Track a view for a listing.
     *
     * Flow:
     * 1. Validate listing exists & is ACTIVE
     * 2. Bot/suspicious detection
     * 3. Redis dedup check (1 view / 30 min / user or guest)
     * 4. Redis atomic INCR counter
     *
     * @param int         $listingId
     * @param int|null    $userId   Null if guest
     * @param string      $ip
     * @param string      $userAgent
     * @param string|null $anonCookie  Anonymous cookie for better guest fingerprinting
     *
     * @return array{counted: bool, reason: string}
     */
    public function trackView(
        int     $listingId,
        ?int    $userId,
        string  $ip,
        string  $userAgent,
        ?string $anonCookie = null,
    ): array;
}
