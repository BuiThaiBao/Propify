<?php

namespace App\DTOs\Auth;

use App\Services\Auth\SocialUserAdapter;

final readonly class SocialAuthPayload implements AuthPayload
{
    public function __construct(
        public SocialUserAdapter $socialUser,
    ) {}
}
