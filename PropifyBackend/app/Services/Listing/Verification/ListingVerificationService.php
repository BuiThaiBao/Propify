<?php

namespace App\Services\Listing\Verification;

use App\Models\Listing;
use App\Models\User;

interface ListingVerificationService
{
    public function requestVerification(User $user, int $listingId, array $documents): Listing;

    public function approveVerification(int $listingId, int $adminUserId): Listing;

    public function rejectVerification(int $listingId, int $adminUserId, string $reason): Listing;
}
