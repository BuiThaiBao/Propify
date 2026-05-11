<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Helpers\ApiResponse;
use App\Services\ViewTracking\ViewTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class ViewTrackingController
{
    public function __construct(
        private readonly ViewTrackingService $viewTrackingService,
    ) {}

    /**
     * POST /api/v1/listings/{id}/view
     *
     * Public endpoint — auth không bắt buộc.
     * Nếu có JWT token → lấy userId để dedup chính xác hơn.
     */
    public function track(Request $request, int $id): JsonResponse
    {
        // Lấy userId nếu có token (optional auth)
        $userId = null;
        try {
            $user = auth('api')->user();
            $userId = $user?->id;
        } catch (\Throwable) {
            // Token invalid/expired → treat as guest
        }

        $ip        = $request->ip();
        $userAgent = $request->userAgent() ?? '';
        $anonCookie = $request->cookie('_pv_anon') ?? $request->header('X-Anon-Id');

        try {
            $result = $this->viewTrackingService->trackView(
                listingId:  $id,
                userId:     $userId,
                ip:         $ip,
                userAgent:  $userAgent,
                anonCookie: $anonCookie,
            );

            return ApiResponse::success(
                data: ['counted' => $result['counted']],
                message: match ($result['reason']) {
                    'view_counted'   => 'View counted',
                    'duplicated_view' => 'View already counted',
                    'bot_detected'   => 'Request rejected',
                    'invalid_listing' => 'Listing not found or inactive',
                    default          => $result['reason'],
                },
            );
        } catch (\Throwable $e) {
            Log::error('ViewTracking: unexpected error', [
                'listing_id' => $id,
                'error'      => $e->getMessage(),
            ]);

            // Fail silently — view tracking should never break UX
            return ApiResponse::success(
                data: ['counted' => false],
                message: 'View tracking temporarily unavailable',
            );
        }
    }
}
