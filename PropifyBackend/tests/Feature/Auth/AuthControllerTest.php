<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Users;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_201_with_token()
    {
        $payload = [
            'full_name'             => 'Test User',
            'phone'                 => '0123456789',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => ['id', 'full_name', 'email', 'phone', 'role', 'status'],
                    'access_token',
                    'token_type',
                    'expires_in'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role'  => UserRole::User->value,
            'status'=> UserStatus::Active->value,
        ]);
    }

    public function test_register_with_status_field_ignores_it()
    {
        $payload = [
            'full_name'             => 'Hacker',
            'phone'                 => '0987654321',
            'email'                 => 'hacker@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'status'                => UserStatus::Banned->value, // Try to ban oneself or something
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201);

        // Security check: Must be Active, ignoring the input
        $this->assertDatabaseHas('users', [
            'email'  => 'hacker@example.com',
            'status' => UserStatus::Active->value,
        ]);
    }

    public function test_login_with_valid_credentials_returns_200()
    {
        $user = Users::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => ['access_token']
            ]);
    }

    public function test_login_with_wrong_password_returns_401()
    {
        $user = Users::factory()->create([
            'password' => bcrypt('password123'),
        ]);

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
        $user = Users::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_invalidates_token()
    {
        $user = Users::factory()->create();
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
}
