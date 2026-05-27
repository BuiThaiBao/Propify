<?php

namespace App\Services\Listing\Validation;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

final class UserPhoneVerifiedHandler extends AbstractListingSubmissionValidationHandler
{
    protected function validate(ListingSubmissionValidationContext $context): void
    {
        if (!$context->user->phone || trim((string) $context->user->phone) === '') {
            throw new BusinessException(ErrorCode::AuthPhoneNotVerified);
        }
    }
}
