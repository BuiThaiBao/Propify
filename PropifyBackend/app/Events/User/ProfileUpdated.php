<?php

namespace App\Events\User;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProfileUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $userId
    ) {}
}
