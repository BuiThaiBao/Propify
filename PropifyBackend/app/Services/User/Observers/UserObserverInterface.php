<?php

namespace App\Services\User\Observers;

use App\Models\User;

interface UserObserverInterface
{
    /**
     * Update the observer with the user event.
     */
    public function update(User $user, string $event, array $metadata = []): void;
}
