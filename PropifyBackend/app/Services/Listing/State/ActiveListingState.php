<?php

namespace App\Services\Listing\State;

final class ActiveListingState extends AbstractListingStatusState
{
    public function value(): string
    {
        return 'ACTIVE';
    }

    protected function allowedTransitions(): array
    {
        return ['LOCKED', 'REJECTED', 'UNLISTED'];
    }
}
