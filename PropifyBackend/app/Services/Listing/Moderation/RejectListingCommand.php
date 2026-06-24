<?php

namespace App\Services\Listing\Moderation;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\Listing;

/**
 * Từ chối tin đăng: chuyển sang REJECTED, bắt buộc có lý do và lưu vào listing.
 */
final class RejectListingCommand extends AbstractListingModerationCommand
{
    protected function targetStatus(): string
    {
        return 'REJECTED';
    }

    protected function validate(ModerationContext $ctx): void
    {
        if (trim((string) $ctx->reason) === '') {
            throw new BusinessException(ErrorCode::BadRequest, 'Vui lòng nhập lý do từ chối.');
        }
    }

    protected function mutate(Listing $listing, ModerationContext $ctx): void
    {
        $listing->status = 'REJECTED';
        $listing->rejection_reason = $ctx->reason;
    }
}
