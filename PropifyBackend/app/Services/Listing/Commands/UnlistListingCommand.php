<?php

namespace App\Services\Listing\Commands;

use App\Enums\ErrorCode;
use App\Events\Listing\ListingSaved;
use App\Exceptions\BusinessException;
use App\Models\Listing;
use App\Models\ListingStatusHistory;
use App\Models\User;
use App\Repositories\ListingRepository;
use App\Services\Listing\State\ListingStatusStateFactory;
use Illuminate\Support\Facades\DB;

final class UnlistListingCommand
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly ListingStatusStateFactory $statusStateFactory,
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

            if ($listing->status === 'UNLISTED') {
                throw new BusinessException(ErrorCode::BadRequest, 'Tin dang da duoc go truoc do.');
            }

            if (! $this->statusStateFactory->make($listing->status)->canTransitionTo('UNLISTED')) {
                throw new BusinessException(ErrorCode::BadRequest, 'Khong cho phep go tin nay.');
            }

            $updated = $this->listingRepository->updateListing($listing->id, [
                'status' => 'UNLISTED',
            ]);

            ListingStatusHistory::create([
                'user_id' => $user->id,
                'listing_id' => $listing->id,
                'action' => 'UNLISTED',
                'reason' => 'User unlisted listing',
            ]);

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
