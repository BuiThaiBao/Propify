<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Support\AuthCookieFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'full_name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123'),
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
        ], $overrides));
    }

    private function makeRefreshToken(User $user): string
    {
        $factory = JWTAuth::factory();
        $originalTtl = $factory->getTTL();

        try {
            $factory->setTTL((int) config('jwt.refresh_ttl', 20160));

            return JWTAuth::claims(['typ' => 'refresh'])->fromUser($user);
        } finally {
            $factory->setTTL($originalTtl);
        }
    }

    public function test_register_returns_202_and_creates_pending_user()
    {
        $payload = [
            'full_name'             => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'status',
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role'  => UserRole::User->value,
            'status'=> UserStatus::Pending->value,
        ]);
    }

    public function test_register_with_status_field_ignores_it()
    {
        $payload = [
            'full_name'             => 'Hacker',
            'email'                 => 'hacker@example.com',
            'password'              => 'Password123',
            'password_confirmation' => 'Password123',
            'status'                => UserStatus::Banned->value, // Try to ban oneself or something
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(202);

        // Security check: Must be Pending, ignoring the input
        $this->assertDatabaseHas('users', [
            'email'  => 'hacker@example.com',
            'status' => UserStatus::Pending->value,
        ]);
    }

    public function test_login_with_valid_credentials_returns_200()
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => ['user', 'token_type', 'expires_in']
            ])
            ->assertCookie(AuthCookieFactory::accessCookieName())
            ->assertCookie(AuthCookieFactory::refreshCookieName());
    }

    public function test_login_with_wrong_password_returns_401()
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', false)
            ->assertJsonPath('error_code', 1001); // ErrorCode::AuthLoginFailed
    }

    public function test_me_without_token_returns_401()
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_me_with_valid_token_returns_user_resource()
    {
        $user = $this->createUser();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_invalidates_token()
    {
        $user = $this->createUser();
        $token = JWTAuth::fromUser($user);

        // First request to logout
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);

        // Second request with same token should fail
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/v1/auth/me');

        $response2->assertStatus(401);
    }

    public function test_refresh_returns_new_token_for_valid_token()
    {
        $this->withoutMiddleware();

        $user = $this->createUser();
        $refreshToken = $this->makeRefreshToken($user);

        $response = $this->call(
            method: 'POST',
            uri: '/api/v1/auth/refresh',
            cookies: [AuthCookieFactory::refreshCookieName() => $refreshToken],
            server: ['HTTP_ACCEPT' => 'application/json'],
        );

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => ['token_type', 'expires_in'],
            ]);

    }

    public function test_refresh_returns_new_token_for_expired_token_inside_refresh_window()
    {
        $this->withoutMiddleware();

        $user = $this->createUser();
        $refreshToken = $this->makeRefreshToken($user);

        $this->travel(config('jwt.ttl') + 1)->minutes();

        $response = $this->call(
            method: 'POST',
            uri: '/api/v1/auth/refresh',
            cookies: [AuthCookieFactory::refreshCookieName() => $refreshToken],
            server: ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->travelBack();

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => ['token_type', 'expires_in'],
            ]);

    }
}
