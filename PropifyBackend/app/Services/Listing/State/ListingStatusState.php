<?php

namespace App\Services\Listing\State;

interface ListingStatusState
{
    public function value(): string;

    public function canTransitionTo(string $nextStatus): bool;
}
