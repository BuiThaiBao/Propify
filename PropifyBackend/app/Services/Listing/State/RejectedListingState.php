<?php

namespace App\Services\Listing\State;

final class RejectedListingState extends AbstractListingStatusState
{
    public function value(): string
    {
        return 'REJECTED';
    }

    protected function allowedTransitions(): array
    {
        return ['ACTIVE', 'PENDING'];
    }
}
