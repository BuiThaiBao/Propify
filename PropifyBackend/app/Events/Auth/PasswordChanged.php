<?php

namespace App\Events\Auth;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PasswordChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $userId
    ) {}
}
