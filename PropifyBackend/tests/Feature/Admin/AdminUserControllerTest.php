<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Listing;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

final class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $admin = User::create([
            'full_name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'Password123',
            'role' => UserRole::Admin->value,
            'status' => UserStatus::Active->value,
        ]);

        $user = User::create([
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
        ]);

        $token = JWTAuth::fromUser($admin);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/users');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonCount(1, 'data') // Excludes admin themselves
            ->assertJsonPath('data.0.id', $user->id)
            ->assertJsonPath('data.0.name', 'John Doe');
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $user = User::create([
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/users');

        $response->assertForbidden();
    }

    public function test_admin_can_search_users(): void
    {
        $admin = User::create([
            'full_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'Password123',
            'role' => UserRole::Admin->value,
            'status' => UserStatus::Active->value,
        ]);

        $user1 = User::create([
            'full_name' => 'Nguyen Van A',
            'email' => 'nva@example.com',
            'password' => 'Password123',
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
            'phone' => '0901234567',
        ]);

        $user2 = User::create([
            'full_name' => 'Tran Thi B',
            'email' => 'ttb@example.com',
            'password' => 'Password123',
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
            'phone' => '0907654321',
        ]);

        $token = JWTAuth::fromUser($admin);

        // Search by name
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/users?search=Nguyen');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $user1->id);

        // Search by email
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/users?search=ttb@example.com');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $user2->id);

        // Search by phone
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/users?search=12345');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $user1->id);
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $admin = User::create([
            'full_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'Password123',
            'role' => UserRole::Admin->value,
            'status' => UserStatus::Active->value,
        ]);

        // Agent (has listing with BROKER poster type)
        $agent = User::create([
            'full_name' => 'Agent User',
            'email' => 'agent@example.com',
            'password' => 'Password123',
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
            'phone' => '0901234567',
        ]);

        $propertyBroker = Property::create([
            'owner_id' => $agent->id,
            'type' => 'APARTMENT',
            'province_code' => '79',
            'area' => 50,
            'poster_type' => 'BROKER',
        ]);

        Listing::create([
            'property_id' => $propertyBroker->id,
            'owner_id' => $agent->id,
            'demand_type' => 'RENT',
            'title' => 'Broker Listing',
            'description' => 'Broker desc',
            'status' => 'ACTIVE',
        ]);

        // Regular User (has no listings)
        $regularUser = User::create([
            'full_name' => 'Regular User',
            'email' => 'regular@example.com',
            'password' => 'Password123',
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
            'phone' => '0901234568',
        ]);

        $token = JWTAuth::fromUser($admin);

        // Filter role = agent
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/users?role=agent');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $agent->id)
            ->assertJsonPath('data.0.role', 'agent');

        // Filter role = user
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/users?role=user');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $regularUser->id)
            ->assertJsonPath('data.0.role', 'user');
    }

    public function test_admin_can_lock_and_unlock_user(): void
    {
        $admin = User::create([
            'full_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'Password123',
            'role' => UserRole::Admin->value,
            'status' => UserStatus::Active->value,
        ]);

        $user = User::create([
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
        ]);

        $token = JWTAuth::fromUser($admin);

        // 1. Lock user
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->patchJson("/api/v1/admin/users/{$user->id}/status", [
            'status' => 'locked',
            'reason_code' => 1,
            'reason_text' => null,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'locked')
            ->assertJsonPath('data.statusLabel', 'Đã khóa');

        $this->assertEquals(UserStatus::Banned, $user->fresh()->status);

        // Assert Audit Log was created
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'action' => 'admin.user.locked',
        ]);

        // Assert Notification was created for locking (Observer Pattern)
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'user_locked',
            'title' => 'Tài khoản của bạn đã bị khóa',
        ]);

        // 2. Unlock user
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->patchJson("/api/v1/admin/users/{$user->id}/status", [
            'status' => 'active',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.statusLabel', 'Hoạt động');

        $this->assertEquals(UserStatus::Active, $user->fresh()->status);

        // Assert Audit Log was created for unlocking
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'action' => 'admin.user.unlocked',
        ]);

        // Assert Notification was created for unlocking (Observer Pattern)
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'user_unlocked',
            'title' => 'Tài khoản của bạn đã được mở khóa',
        ]);
    }
}
