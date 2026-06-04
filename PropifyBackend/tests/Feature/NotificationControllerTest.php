<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_own_notifications_and_unread_count(): void
    {
        $user = User::query()->create(['phone' => '0900000001']);
        $otherUser = User::query()->create(['phone' => '0900000002', 'email' => 'other@example.com']);

        Notification::query()->create([
            'user_id' => $user->id,
            'type' => 'package_upgraded',
            'title' => 'A',
            'content' => 'A',
            'data' => ['listing_id' => 1],
        ]);
        Notification::query()->create([
            'user_id' => $user->id,
            'type' => 'package_expiring',
            'title' => 'B',
            'content' => 'B',
            'data' => ['listing_id' => 2],
            'read_at' => now(),
        ]);
        Notification::query()->create([
            'user_id' => $otherUser->id,
            'type' => 'package_upgraded',
            'title' => 'C',
            'content' => 'C',
            'data' => ['listing_id' => 3],
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/notifications');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.unread_count', 1);
    }

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $user = User::query()->create(['phone' => '0900000003', 'email' => 'u3@example.com']);
        $notification = Notification::query()->create([
            'user_id' => $user->id,
            'type' => 'package_upgraded',
            'title' => 'A',
            'content' => 'A',
        ]);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::query()->create(['phone' => '0900000004', 'email' => 'u4@example.com']);

        Notification::query()->create([
            'user_id' => $user->id,
            'type' => 'package_upgraded',
            'title' => 'A',
            'content' => 'A',
        ]);
        Notification::query()->create([
            'user_id' => $user->id,
            'type' => 'package_expiring',
            'title' => 'B',
            'content' => 'B',
        ]);

        $response = $this->actingAs($user, 'api')
            ->patchJson('/api/v1/notifications/read-all');

        $response->assertOk();
        $this->assertSame(0, $user->notifications()->unread()->count());
    }

    public function test_user_can_get_unread_notification_count(): void
    {
        $user = User::query()->create(['phone' => '0900000005', 'email' => 'u5@example.com']);

        Notification::query()->create([
            'user_id' => $user->id,
            'type' => 'package_upgraded',
            'title' => 'A',
            'content' => 'A',
        ]);
        Notification::query()->create([
            'user_id' => $user->id,
            'type' => 'package_expiring',
            'title' => 'B',
            'content' => 'B',
            'read_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertOk();
        $response->assertJsonPath('data.count', 1);
    }
}
