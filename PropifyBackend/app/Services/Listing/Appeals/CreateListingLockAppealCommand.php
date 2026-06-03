<?php

namespace App\Services\Listing\Appeals;

use App\Models\Listing;
use App\Models\ListingLockAppeal;
use App\Models\User;

final readonly class CreateListingLockAppealCommand
{
    public function handle(User $user, Listing $listing, string $reason): ListingLockAppeal
    {
        return ListingLockAppeal::query()->create([
            'listing_id' => $listing->id,
            'user_id' => $user->id,
            'reason' => trim($reason),
            'status' => ListingLockAppeal::STATUS_PENDING,
        ]);
    }

    public function hasPendingAppeal(User $user, Listing $listing): bool
    {
        return ListingLockAppeal::query()
            ->where('listing_id', $listing->id)
            ->where('user_id', $user->id)
            ->where('status', ListingLockAppeal::STATUS_PENDING)
            ->exists();
    }
}
