<?php

namespace App\Services\Listing\Validation;

final class ListingSubmissionValidationPipeline
{
    public function __construct(
        private readonly UserPhoneVerifiedHandler $userPhoneVerifiedHandler,
        private readonly VerificationDocumentsHandler $verificationDocumentsHandler,
    ) {
    }

    public function validate(ListingSubmissionValidationContext $context): void
    {
        $this->userPhoneVerifiedHandler
            ->setNext($this->verificationDocumentsHandler);

        $this->userPhoneVerifiedHandler->handle($context);
    }
}
