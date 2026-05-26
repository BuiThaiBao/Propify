<?php

namespace App\Services\Listing\Validation;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

final class VerificationDocumentsHandler extends AbstractListingSubmissionValidationHandler
{
    protected function validate(ListingSubmissionValidationContext $context): void
    {
        if (!$context->dto->requestVerification) {
            return;
        }

        if (!$context->dto->identityCardFront || !$context->dto->identityCardBack) {
            throw new BusinessException(ErrorCode::ValidationError, 'Can tai len day du CCCD mat truoc va mat sau de gui yeu cau xac thuc.');
        }
    }
}
