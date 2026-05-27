<?php

namespace App\Services\Listing\State;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

final class ListingStatusStateFactory
{
    public function make(string $status): ListingStatusState
    {
        return match ($status) {
            'DRAFT' => new DraftListingState(),
            'PENDING' => new PendingListingState(),
            'ACTIVE' => new ActiveListingState(),
            'REJECTED' => new RejectedListingState(),
            'LOCKED' => new LockedListingState(),
            'UNLISTED' => new UnlistedListingState(),
            default => throw new BusinessException(ErrorCode::BadRequest, "Trang thai listing {$status} khong hop le."),
        };
    }

    public function initialForSave(bool $saveAsDraft): ListingStatusState
    {
        return $saveAsDraft ? new DraftListingState() : new PendingListingState();
    }

    public function assertCanTransition(string $currentStatus, string $nextStatus): void
    {
        if ($currentStatus === $nextStatus) {
            throw new BusinessException(ErrorCode::BadRequest, "Listing da o trang thai {$nextStatus}.");
        }

        $currentState = $this->make($currentStatus);

        if (!$currentState->canTransitionTo($nextStatus)) {
            throw new BusinessException(ErrorCode::BadRequest, "Trang thai hien tai cua listing khong ho tro chuyen sang trang thai {$nextStatus}.");
        }
    }
}
