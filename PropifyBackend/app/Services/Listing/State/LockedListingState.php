<?php

namespace App\Services\Listing\State;

final class LockedListingState extends AbstractListingStatusState
{
    public function value(): string
    {
        return 'LOCKED';
    }

    protected function allowedTransitions(): array
    {
        return ['ACTIVE'];
    }
}
