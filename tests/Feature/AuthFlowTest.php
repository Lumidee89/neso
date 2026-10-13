<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_an_email_verification_otp(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone' => '08011112222',
            'password' => 'password123',
            'user_type' => 'vendor',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'status' => 'pending_verification',
        ]);

        $this->assertDatabaseHas('otp_codes', [
            'user_id' => $response->json('user_id'),
            'purpose' => 'email_verification',
        ]);
    }

    public function test_login_is_rejected_until_email_is_verified(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'jane@example.com', 'password' => bcrypt('password123')]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)->assertJson(['requires_email_verification' => true]);
    }

    public function test_otp_verification_marks_email_verified_and_allows_login(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'jane@example.com', 'password' => bcrypt('password123')]);
        $otp = OtpCode::create([
            'user_id' => $user->id,
            'code' => '123456',
            'purpose' => 'email_verification',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/v1/otp/verify', [
            'email' => 'jane@example.com',
            'purpose' => 'email_verification',
            'code' => '123456',
        ])->assertOk();

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->postJson('/api/v1/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_wallet_is_auto_created_when_user_status_becomes_verified(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->wallet);

        $user->update(['status' => 'verified']);

        $this->assertNotNull($user->fresh()->wallet);
    }
}
