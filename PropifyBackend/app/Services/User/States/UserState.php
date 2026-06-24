<?php

namespace App\Services\User\States;

use App\Enums\UserStatus;
use App\Models\User;

abstract class UserState
{
    /**
     * UserState constructor.
     */
    public function __construct(protected User $user) {}

    /**
     * Lock/Ban the user account.
     */
    abstract public function lock(): void;

    /**
     * Unlock/Activate the user account.
     */
    abstract public function unlock(): void;

    /**
     * Get the representative status enum.
     */
    abstract public function getStatus(): UserStatus;
}
