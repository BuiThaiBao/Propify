<?php

namespace App\Services\User\Visibility;

use App\Models\User;

final class OwnerVisibilityStrategy implements VisibilityStrategy
{
    public function filter(User $target, array $data): array
    {
        return $data;
    }
}
