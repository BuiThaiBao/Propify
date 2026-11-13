<?php

namespace App\Http\Controllers\Api\V1\Listing;

use App\Helpers\ApiResponse;
use App\Http\Requests\Listing\UpgradeListingRequest;
use App\Models\Transaction;
use App\Services\Listing\ListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListingUpgradeController
{
    public function __construct(
        private readonly ListingService $listingService,
    ) {}

    public function upgrade(UpgradeListingRequest $request, int $id): JsonResponse
    {
        $payment = $this->listingService->createUpgradePayment(
            user: $request->user(),
            listingId: $id,
            packageId: (int) $request->validated('package_id'),
            durationDays: (int) $request->validated('duration_days'),
        );

        return ApiResponse::success(
            data: $payment,
            message: 'Đã tạo yêu cầu thanh toán.'
        );
    }

    public function paymentStatus(Request $request, int $id, int $transactionId): JsonResponse
    {
        $transaction = Transaction::query()
            ->where('listing_id', $id)
            ->where('user_id', $request->user()->id)
            ->whereIn('payment_method', ['SEPAY', 'SEPAY_GATEWAY'])
            ->findOrFail($transactionId);

        $status = $transaction->status;
        if ($status === 'PENDING' && ! $transaction->expires_at?->isFuture()) {
            $status = 'EXPIRED';
        }

        return ApiResponse::success(data: ['status' => $status]);
    }
}
