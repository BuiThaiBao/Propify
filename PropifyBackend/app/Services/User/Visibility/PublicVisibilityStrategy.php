<?php

namespace App\Services\User\Visibility;

use App\Models\User;

final class PublicVisibilityStrategy implements VisibilityStrategy
{
    public function filter(User $target, array $data): array
    {
        $data['email'] = null;
        $data['phone'] = null;

        return $data;
    }
}
