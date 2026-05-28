<?php

namespace App\Services\Listing\State;

final class UnlistedListingState extends AbstractListingStatusState
{
    public function value(): string
    {
        return 'UNLISTED';
    }

    protected function allowedTransitions(): array
    {
        return [];
    }
}
