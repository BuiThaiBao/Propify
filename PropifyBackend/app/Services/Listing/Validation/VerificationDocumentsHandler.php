<?php

namespace App\Services\Listing\Validation;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Support\ListingVerificationStatusResolver;

final class VerificationDocumentsHandler extends AbstractListingSubmissionValidationHandler
{
    protected function validate(ListingSubmissionValidationContext $context): void
    {
        if (! $context->dto->requestVerification) {
            return;
        }

        if (! ListingVerificationStatusResolver::hasCompleteDocuments(
            $context->dto->identityCardFront,
            $context->dto->identityCardBack,
            $context->dto->legalDocuments,
        )) {
            throw new BusinessException(ErrorCode::ValidationError, 'Can tai len day du CCCD mat truoc, mat sau va it nhat mot anh giay to phap ly de gui yeu cau xac thuc.');
        }
    }
}
