<?php

namespace App\Services\Listing\State;

final class DraftListingState extends AbstractListingStatusState
{
    public function value(): string
    {
        return 'DRAFT';
    }

    protected function allowedTransitions(): array
    {
        return ['PENDING'];
    }
}
