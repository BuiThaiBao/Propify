<?php

namespace App\Services\User\States;

use App\Enums\UserStatus;

final class ActiveUserState extends UserState
{
    /**
     * {@inheritdoc}
     */
    public function lock(): void
    {
        $this->user->status = UserStatus::Banned;
        $this->user->save();
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(): void
    {
        // Account is already active. No state change needed.
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): UserStatus
    {
        return UserStatus::Active;
    }
}
