<?php

namespace App\Services\Listing\Reports\Rules;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\ListingReport;
use App\Services\Listing\Reports\ListingReportContext;

final class PreventDuplicateListingReportHandler extends ListingReportValidationHandler
{
    protected function validate(ListingReportContext $context): void
    {
        $hasRecentReport = ListingReport::query()
            ->where('listing_id', $context->listing->id)
            ->where('reporter_id', $context->reporter->id)
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($hasRecentReport) {
            throw new BusinessException(
                ErrorCode::BadRequest,
                'Bạn đã gửi báo cáo cho tin này gần đây. Vui lòng thử lại sau.'
            );
        }
    }
}
