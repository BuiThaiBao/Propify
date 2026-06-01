<?php

namespace Tests\Unit\Auth;

use App\DTOs\Auth\SocialAuthPayload;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Auth\AuthTokenIssuer;
use App\Services\Auth\SocialUserAdapter;
use App\Services\Auth\Strategies\GoogleOAuthAuthStrategy;
use App\Services\Auth\UserUpsertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class GoogleOAuthAuthStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_authenticates_a_social_user_via_user_upsert_service(): void
    {
        $user = User::create([
            'full_name' => 'Google User',
            'email' => 'google@example.com',
            'password' => null,
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
        ]);

        $socialUser = new class implements SocialUserAdapter
        {
            public function getProviderName(): string
            {
                return 'google';
            }

            public function getProviderId(): string
            {
                return 'google-123';
            }

            public function getName(): string
            {
                return 'Google User';
            }

            public function getEmail(): string
            {
                return 'google@example.com';
            }

            public function getAvatarUrl(): ?string
            {
                return null;
            }
        };

        $upsertService = Mockery::mock(UserUpsertService::class);
        $upsertService
            ->shouldReceive('upsertFromSocial')
            ->once()
            ->with($socialUser)
            ->andReturn($user);

        $strategy = new GoogleOAuthAuthStrategy(
            $upsertService,
            app(AuthTokenIssuer::class),
        );

        $result = $strategy->authenticate(new SocialAuthPayload($socialUser));

        $this->assertSame($user->id, $result->userId);
        $this->assertSame(UserRole::User->value, $result->role);
        $this->assertNotSame('', $result->accessToken);
    }
}
