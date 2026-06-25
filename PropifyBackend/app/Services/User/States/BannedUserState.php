<?php

namespace App\Services\User\States;

use App\Enums\UserStatus;

final class BannedUserState extends UserState
{
    /**
     * {@inheritdoc}
     */
    public function lock(): void
    {
        // Account is already banned. No state change needed.
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(): void
    {
        $this->user->status = UserStatus::Active;
        $this->user->save();
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): UserStatus
    {
        return UserStatus::Banned;
    }
}
