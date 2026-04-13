<?php

namespace App\Services\Auth\Adapters;

use App\Services\Auth\SocialUserAdapter;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Adapter bọc Socialite Google User → SocialUserAdapter domain interface.
 *
 * Isolate Socialite SDK khỏi business logic.
 * Khi Socialite thay đổi API → chỉ sửa adapter này.
 */
final class GoogleSocialiteAdapter implements SocialUserAdapter
{
    public function __construct(
        private readonly SocialiteUser $socialiteUser
    ) {}

    public function getProviderName(): string
    {
        return 'google';
    }

    public function getProviderId(): string
    {
        return $this->socialiteUser->getId();
    }

    public function getName(): string
    {
        return $this->socialiteUser->getName();
    }

    public function getEmail(): string
    {
        return $this->socialiteUser->getEmail();
    }

    public function getAvatarUrl(): ?string
    {
        return $this->socialiteUser->getAvatar();
    }
}
