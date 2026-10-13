<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_creates_a_notification_for_the_user(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'login',
        ]);
    }

    public function test_user_can_list_only_their_own_notifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        AppNotification::create([
            'user_id' => $user->id,
            'channel' => 'push',
            'type' => 'login',
            'title' => 'New login',
        ]);
        AppNotification::create([
            'user_id' => $other->id,
            'channel' => 'push',
            'type' => 'login',
            'title' => 'New login',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications');

        $response->assertOk();
        $this->assertCount(1, $response->json());
    }

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = AppNotification::create([
            'user_id' => $user->id,
            'channel' => 'push',
            'type' => 'login',
            'title' => 'New login',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notification = AppNotification::create([
            'user_id' => $other->id,
            'channel' => 'push',
            'type' => 'login',
            'title' => 'New login',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertForbidden();
    }
}
