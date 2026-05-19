<?php

namespace App\Services\Listing\Validation;

interface ListingSubmissionValidationHandler
{
    public function setNext(?ListingSubmissionValidationHandler $next): ListingSubmissionValidationHandler;

    public function handle(ListingSubmissionValidationContext $context): void;
}
