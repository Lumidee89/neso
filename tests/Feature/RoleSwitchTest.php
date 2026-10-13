<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_payer_can_switch_to_vendor(): void
    {
        $user = User::factory()->create(['user_type' => 'payer']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/profile/switch-role', [
            'user_type' => 'vendor',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['user_type' => 'vendor']);
        $this->assertSame('vendor', $user->fresh()->user_type);
    }

    public function test_vendor_can_switch_to_source(): void
    {
        $user = User::factory()->create(['user_type' => 'vendor']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/profile/switch-role', [
            'user_type' => 'source',
        ])->assertOk();

        $this->assertSame('source', $user->fresh()->user_type);
    }

    public function test_cannot_switch_to_admin(): void
    {
        $user = User::factory()->create(['user_type' => 'payer']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/profile/switch-role', [
            'user_type' => 'admin',
        ])->assertStatus(422);

        $this->assertSame('payer', $user->fresh()->user_type);
    }

    public function test_admin_account_cannot_switch_role(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/profile/switch-role', [
            'user_type' => 'payer',
        ])->assertForbidden();

        $this->assertSame('admin', $admin->fresh()->user_type);
    }
}
