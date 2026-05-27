<?php

namespace App\Services\Listing\Validation;

abstract class AbstractListingSubmissionValidationHandler implements ListingSubmissionValidationHandler
{
    private ?ListingSubmissionValidationHandler $next = null;

    public function setNext(?ListingSubmissionValidationHandler $next): ListingSubmissionValidationHandler
    {
        $this->next = $next;

        return $next ?? $this;
    }

    public function handle(ListingSubmissionValidationContext $context): void
    {
        $this->validate($context);
        $this->next?->handle($context);
    }

    abstract protected function validate(ListingSubmissionValidationContext $context): void;
}
