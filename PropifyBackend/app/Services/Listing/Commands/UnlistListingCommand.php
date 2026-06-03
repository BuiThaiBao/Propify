<?php

namespace App\Services\Listing\Commands;

use App\Enums\ErrorCode;
use App\Events\Listing\ListingSaved;
use App\Exceptions\BusinessException;
use App\Models\Listing;
use App\Models\ListingStatusHistory;
use App\Models\User;
use App\Repositories\ListingRepository;
use Illuminate\Support\Facades\DB;

final class UnlistListingCommand
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
    ) {}

    public function handle(User $user, int $listingId): Listing
    {
        return DB::transaction(function () use ($user, $listingId) {
            $listing = Listing::query()->lockForUpdate()->find($listingId);

            if (! $listing) {
                throw new BusinessException(ErrorCode::ListingNotFound);
            }

            if ((int) $listing->owner_id !== (int) $user->id) {
                throw new BusinessException(ErrorCode::AuthForbidden);
            }

            $updated = $listing;

            if ($listing->status !== 'UNLISTED') {
                $updated = $this->listingRepository->updateListing($listing->id, [
                    'status' => 'UNLISTED',
                ]);

                ListingStatusHistory::create([
                    'user_id' => $user->id,
                    'listing_id' => $listing->id,
                    'action' => 'UNLISTED',
                    'reason' => 'User unlisted listing',
                ]);
            }

            $loaded = $updated->load([
                'property.attributes.group',
                'images',
                'videos',
                'verificationDocuments',
                'appointmentSlots',
                'appointments',
                'owner',
                'approver',
                'package',
                'statusHistories.user',
            ]);

            ListingSaved::dispatch($loaded, $user, 'unlisted');

            return $loaded;
        });
    }
}
