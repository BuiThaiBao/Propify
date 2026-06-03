<?php

namespace App\Listeners\Listing;

use App\Events\Listing\ListingSaved;
use App\Models\ListingStatusHistory;
use App\Services\Notification\InAppNotificationService;

final class CreateListingNotification
{
    public function __construct(
        private readonly InAppNotificationService $notificationService,
    ) {}

    public function handle(ListingSaved $event): void
    {
        $listing = $event->listing->loadMissing('owner');
        $ownerId = (int) $listing->owner_id;

        if ($ownerId <= 0) {
            return;
        }

        $payload = match ($event->action) {
            'updated' => $this->updatedPendingPayload($listing),
            'unlisted' => $this->statusPayload($listing, 'UNLISTED'),
            'admin_status_changed' => $this->statusPayload($listing, (string) $listing->status),
            default => null,
        };

        if ($payload === null) {
            return;
        }

        $this->notificationService->create(
            userId: $ownerId,
            type: $payload['type'],
            title: $payload['title'],
            message: $payload['message'],
            data: $payload['data'],
        );
    }

    private function updatedPendingPayload($listing): ?array
    {
        if ($listing->status !== 'PENDING') {
            return null;
        }

        return $this->payload(
            listing: $listing,
            type: 'LISTING_UPDATED_PENDING',
            title: 'Tin đăng đang chờ duyệt lại',
            message: "Tin đăng \"{$listing->title}\" đã được cập nhật và đang chờ duyệt lại.",
            status: 'PENDING',
        );
    }

    private function statusPayload($listing, string $status): ?array
    {
        $reason = $this->latestStatusReason((int) $listing->id, $status) ?? $listing->rejection_reason;

        return match ($status) {
            'ACTIVE' => $this->payload(
                listing: $listing,
                type: 'LISTING_APPROVED',
                title: 'Tin đăng đã được duyệt',
                message: "Tin đăng \"{$listing->title}\" đã được duyệt và hiển thị công khai.",
                status: $status,
                actionUrl: "/listings/{$listing->id}",
            ),
            'REJECTED' => $this->payload(
                listing: $listing,
                type: 'LISTING_REJECTED',
                title: 'Tin đăng bị từ chối',
                message: trim((string) $reason) !== ''
                    ? "Tin đăng \"{$listing->title}\" bị từ chối. Lý do: {$reason}"
                    : "Tin đăng \"{$listing->title}\" bị từ chối.",
                status: $status,
                reason: $reason,
            ),
            'LOCKED' => $this->payload(
                listing: $listing,
                type: 'LISTING_LOCKED',
                title: 'Tin đăng đã bị khóa',
                message: "Tin đăng \"{$listing->title}\" đã bị khóa.",
                status: $status,
                reason: $reason,
            ),
            'UNLISTED' => $this->payload(
                listing: $listing,
                type: 'LISTING_UNLISTED',
                title: 'Tin đăng đã được gỡ',
                message: "Tin đăng \"{$listing->title}\" đã được gỡ khỏi danh sách công khai.",
                status: $status,
                reason: $reason,
            ),
            default => null,
        };
    }

    private function payload(
        $listing,
        string $type,
        string $title,
        string $message,
        string $status,
        ?string $reason = null,
        ?string $actionUrl = null,
    ): array {
        return [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => [
                'listing_id' => $listing->id,
                'listing_code' => sprintf('LH-%08d', $listing->id),
                'status' => $status,
                'action_url' => $actionUrl ?? '/profile?tab=listings',
                'reason' => $reason,
            ],
        ];
    }

    private function latestStatusReason(int $listingId, string $status): ?string
    {
        return ListingStatusHistory::query()
            ->where('listing_id', $listingId)
            ->where('action', $status)
            ->latest('created_at')
            ->value('reason');
    }
}
