<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Helpers\ApiResponse;
use App\Http\Requests\Listing\UpgradeListingRequest;
use App\Services\Listing\ListingService;
use Illuminate\Http\JsonResponse;

final class ListingUpgradeController
{
    public function __construct(
        private readonly ListingService $listingService,
    ) {}

    /**
     * Create a VNPAY payment URL for a listing package upgrade.
     */
    public function upgrade(UpgradeListingRequest $request, int $id): JsonResponse
    {
        $paymentUrl = $this->listingService->createUpgradePayment(
            user: $request->user(),
            listingId: $id,
            packageId: (int) $request->validated('package_id'),
            durationDays: (int) $request->validated('duration_days'),
            clientIp: $request->ip() ?? '127.0.0.1',
        );

        return ApiResponse::success(
            data: ['payment_url' => $paymentUrl],
            message: 'Tao lien ket thanh toan VNPAY thanh cong.'
        );
    }
}
