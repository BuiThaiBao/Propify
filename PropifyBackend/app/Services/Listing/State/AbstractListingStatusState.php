<?php

namespace App\Services\Listing\State;

abstract class AbstractListingStatusState implements ListingStatusState
{
    /**
     * @return string[]
     */
    abstract protected function allowedTransitions(): array;

    public function canTransitionTo(string $nextStatus): bool
    {
        return in_array($nextStatus, $this->allowedTransitions(), true);
    }
}
