<?php

namespace App\Services\Listing\Moderation;

use App\Models\Listing;

/**
 * Khóa tin đăng: chuyển sang LOCKED.
 */
final class LockListingCommand extends AbstractListingModerationCommand
{
    protected function targetStatus(): string
    {
        return 'LOCKED';
    }

    protected function mutate(Listing $listing, ModerationContext $ctx): void
    {
        $listing->status = 'LOCKED';
    }
}
