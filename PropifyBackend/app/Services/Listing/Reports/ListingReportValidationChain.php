<?php

namespace App\Services\Listing\Reports;

use App\Services\Listing\Reports\Rules\ListingReportValidationHandler;

final readonly class ListingReportValidationChain
{
    public function __construct(
        private ListingReportValidationHandler $firstHandler,
    ) {
    }

    public function validate(ListingReportContext $context): void
    {
        $this->firstHandler->handle($context);
    }
}
