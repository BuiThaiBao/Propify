<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

final class AdminAuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_audit_logs(): void
    {
        $admin = User::create([
            'full_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'Password123',
            'role' => UserRole::Admin->value,
            'status' => UserStatus::Active->value,
        ]);

        AuditLog::create([
            'actor_id' => $admin->id,
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
            'action' => 'user.profile.updated',
            'changes' => ['full_name' => ['old' => 'Old', 'new' => 'Admin']],
            'metadata' => ['changed_fields' => ['full_name']],
        ]);

        $token = JWTAuth::fromUser($admin);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/audit-logs');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.0.action', 'user.profile.updated')
            ->assertJsonPath('data.0.actor.id', $admin->id)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_non_admin_cannot_list_audit_logs(): void
    {
        $user = User::create([
            'full_name' => 'User',
            'email' => 'user@example.com',
            'password' => 'Password123',
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/audit-logs');

        $response->assertForbidden();
    }

    public function test_admin_can_filter_audit_logs_by_date_range(): void
    {
        $admin = User::create([
            'full_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'Password123',
            'role' => UserRole::Admin->value,
            'status' => UserStatus::Active->value,
        ]);

        $oldLog = AuditLog::create([
            'actor_id' => $admin->id,
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
            'action' => 'old.action',
            'changes' => [],
            'metadata' => [],
        ]);
        $oldLog->forceFill(['created_at' => '2026-05-10 09:00:00'])->save();

        $inRangeLog = AuditLog::create([
            'actor_id' => $admin->id,
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
            'action' => 'in.range.action',
            'changes' => [],
            'metadata' => [],
        ]);
        $inRangeLog->forceFill(['created_at' => '2026-05-17 09:00:00'])->save();

        $token = JWTAuth::fromUser($admin);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/admin/audit-logs?from_date=2026-05-17&to_date=2026-05-17');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.action', 'in.range.action');
    }
}
