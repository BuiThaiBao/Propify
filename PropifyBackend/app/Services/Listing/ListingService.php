<?php

namespace App\Services\Listing;

use App\DTOs\Listing\CreateListingDto;
use App\Models\Listing;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ListingService
{
    public function create(User $user, CreateListingDto $dto): Listing;

    public function getMyListings(User $user, ?string $status, ?string $demandType, ?string $keyword, int $perPage): LengthAwarePaginator;

    public function getListingDetails(int $id): Listing;

    public function getOwnedListingDetails(User $user, int $id): Listing;

    public function update(User $user, int $id, CreateListingDto $dto): Listing;

    public function updateVerification(User $user, int $id, array $payload): Listing;

    public function lock(User $user, int $id): Listing;

    public function getPublicListings(?string $sortBy, ?string $demandType, ?string $keyword, int $perPage): LengthAwarePaginator;

    public function getAllForAdmin(?string $status, ?string $demandType, ?string $keyword, int $perPage): LengthAwarePaginator;

    public function changeStatusForAdmin(int $listingId, string $status, ?string $rejectionReason = null, ?int $adminUserId = null): Listing;

    public function updateVerificationForAdmin(int $listingId, bool $isVerified, ?string $reason = null, ?int $adminUserId = null): Listing;

    public function upgradeListing(User $user, int $listingId, int $packageId, int $durationDays): Listing;

    public function createUpgradePayment(User $user, int $listingId, int $packageId, int $durationDays, string $clientIp): string;

    public function completePaidUpgrade(Transaction $transaction): Listing;

}
