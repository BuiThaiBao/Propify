<?php

namespace App\Services\Listing\State;

final class PendingListingState extends AbstractListingStatusState
{
    public function value(): string
    {
        return 'PENDING';
    }

    protected function allowedTransitions(): array
    {
        return ['ACTIVE', 'REJECTED'];
    }
}
