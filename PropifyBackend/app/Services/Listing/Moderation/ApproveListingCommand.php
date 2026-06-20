<?php

namespace App\Services\Listing\Moderation;

use App\Models\Listing;

/**
 * Duyệt tin đăng: chuyển sang ACTIVE, ghi nhận thời điểm publish và admin duyệt.
 */
final class ApproveListingCommand extends AbstractListingModerationCommand
{
    protected function targetStatus(): string
    {
        return 'ACTIVE';
    }

    protected function mutate(Listing $listing, ModerationContext $ctx): void
    {
        $listing->status = 'ACTIVE';
        $listing->published_at = now();
        $listing->approved_by = $ctx->adminUserId;
    }
}
