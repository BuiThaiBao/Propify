<?php

namespace App\Services\User\Visibility;

use App\Models\User;

interface VisibilityStrategy
{
    public function filter(User $target, array $data): array;
}
