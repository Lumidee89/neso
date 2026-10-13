<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_their_profile(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'business_name' => null,
            'address' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/v1/profile', [
            'first_name' => 'New',
            'last_name' => 'Name',
            'business_name' => 'Acme Ltd',
            'address' => '12 Marina Road',
            'business_address' => '4 Broad Street',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['first_name' => 'New', 'business_name' => 'Acme Ltd']);

        $user->refresh();
        $this->assertSame('New', $user->first_name);
        $this->assertSame('Acme Ltd', $user->business_name);
        $this->assertSame('12 Marina Road', $user->address);
        $this->assertSame('4 Broad Street', $user->business_address);
    }

    public function test_profile_update_does_not_allow_changing_email_or_status(): void
    {
        $user = User::factory()->create(['email' => 'original@example.com', 'status' => 'verified']);

        $this->actingAs($user, 'sanctum')->patchJson('/api/v1/profile', [
            'email' => 'hacked@example.com',
            'status' => 'suspended',
        ])->assertOk();

        $user->refresh();
        $this->assertSame('original@example.com', $user->email);
        $this->assertSame('verified', $user->status);
    }

    public function test_first_name_cannot_be_blanked_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->patchJson('/api/v1/profile', [
            'first_name' => '',
        ])->assertStatus(422);
    }
}
