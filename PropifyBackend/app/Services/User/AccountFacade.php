<?php

namespace App\Services\User;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\User\Visibility\OwnerVisibilityStrategy;
use App\Services\User\Visibility\PublicVisibilityStrategy;

final class AccountFacade
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly OwnerVisibilityStrategy $ownerVisibilityStrategy,
        private readonly PublicVisibilityStrategy $publicVisibilityStrategy,
    ) {}

    public function getAccountOverview(User $viewer, int $targetUserId): User
    {
        $target = $this->userRepository->findById($targetUserId);
        if (! $target) {
            return $viewer;
        }

        $data = $target->toArray();
        $filtered = ((int) $viewer->id === (int) $target->id)
            ? $this->ownerVisibilityStrategy->filter($target, $data)
            : $this->publicVisibilityStrategy->filter($target, $data);

        $target->forceFill($filtered);

        return $target;
    }
}
