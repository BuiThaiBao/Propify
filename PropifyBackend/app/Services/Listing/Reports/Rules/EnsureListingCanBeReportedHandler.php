<?php

namespace App\Services\Listing\Reports\Rules;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Services\Listing\Reports\ListingReportContext;

final class EnsureListingCanBeReportedHandler extends ListingReportValidationHandler
{
    protected function validate(ListingReportContext $context): void
    {
        if ($context->listing->status !== 'ACTIVE') {
            throw new BusinessException(
                ErrorCode::ListingNotActive,
                'Chỉ có thể báo cáo tin đăng đang hiển thị.'
            );
        }
    }
}
