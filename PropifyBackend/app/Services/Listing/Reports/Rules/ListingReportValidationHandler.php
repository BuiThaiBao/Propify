<?php

namespace App\Services\Listing\Reports\Rules;

use App\Services\Listing\Reports\ListingReportContext;

abstract class ListingReportValidationHandler
{
    private ?self $next = null;

    public function setNext(self $handler): self
    {
        $this->next = $handler;

        return $handler;
    }

    final public function handle(ListingReportContext $context): void
    {
        $this->validate($context);

        if ($this->next !== null) {
            $this->next->handle($context);
        }
    }

    abstract protected function validate(ListingReportContext $context): void;
}
