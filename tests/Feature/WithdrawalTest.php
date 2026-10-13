<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_request_a_withdrawal(): void
    {
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        Wallet::create(['user_id' => $vendor->id, 'available_balance' => 10000]);

        $response = $this->actingAs($vendor, 'sanctum')->postJson('/api/v1/withdrawals', [
            'amount' => 4000,
            'destination_bank' => 'GTBank',
            'destination_account_number' => '0123456789',
            'destination_account_name' => 'Victor Vend',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $vendor->id,
            'amount' => 4000,
            'status' => 'pending',
        ]);
        $this->assertEquals(10000, Wallet::where('user_id', $vendor->id)->value('available_balance'));
    }

    public function test_withdrawal_request_fails_with_insufficient_balance(): void
    {
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        Wallet::create(['user_id' => $vendor->id, 'available_balance' => 100]);

        $this->actingAs($vendor, 'sanctum')->postJson('/api/v1/withdrawals', [
            'amount' => 4000,
            'destination_bank' => 'GTBank',
            'destination_account_number' => '0123456789',
            'destination_account_name' => 'Victor Vend',
        ])->assertStatus(422);
    }

    public function test_admin_approving_debits_the_wallet(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->assignRole('super-admin');

        $vendor = User::factory()->create(['user_type' => 'vendor']);
        $wallet = Wallet::create(['user_id' => $vendor->id, 'available_balance' => 10000]);
        $withdrawal = Withdrawal::create([
            'user_id' => $vendor->id,
            'wallet_id' => $wallet->id,
            'amount' => 4000,
            'status' => 'pending',
            'destination_bank' => 'GTBank',
            'destination_account_number' => '0123456789',
            'destination_account_name' => 'Victor Vend',
            'reference' => 'WD-TEST-1',
        ]);

        $this->actingAs($admin)->post("/admin/withdrawals/{$withdrawal->id}/approve")->assertRedirect();

        $this->assertEquals(6000, $wallet->fresh()->available_balance);
        $this->assertSame('completed', $withdrawal->fresh()->status);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'withdrawal',
            'amount' => 4000,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $vendor->id,
            'type' => 'withdrawal_approved',
        ]);
    }

    public function test_admin_rejecting_does_not_touch_the_wallet(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->assignRole('super-admin');

        $vendor = User::factory()->create(['user_type' => 'vendor']);
        $wallet = Wallet::create(['user_id' => $vendor->id, 'available_balance' => 10000]);
        $withdrawal = Withdrawal::create([
            'user_id' => $vendor->id,
            'wallet_id' => $wallet->id,
            'amount' => 4000,
            'status' => 'pending',
            'destination_bank' => 'GTBank',
            'destination_account_number' => '0123456789',
            'destination_account_name' => 'Victor Vend',
            'reference' => 'WD-TEST-2',
        ]);

        $this->actingAs($admin)->post("/admin/withdrawals/{$withdrawal->id}/reject")->assertRedirect();

        $this->assertEquals(10000, $wallet->fresh()->available_balance);
        $this->assertSame('failed', $withdrawal->fresh()->status);
    }

    public function test_support_agent_cannot_approve_withdrawals(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $agent = User::factory()->create(['user_type' => 'admin']);
        $agent->assignRole('support-agent');

        $vendor = User::factory()->create(['user_type' => 'vendor']);
        $wallet = Wallet::create(['user_id' => $vendor->id, 'available_balance' => 10000]);
        $withdrawal = Withdrawal::create([
            'user_id' => $vendor->id,
            'wallet_id' => $wallet->id,
            'amount' => 4000,
            'status' => 'pending',
            'destination_bank' => 'GTBank',
            'destination_account_number' => '0123456789',
            'destination_account_name' => 'Victor Vend',
            'reference' => 'WD-TEST-3',
        ]);

        $this->actingAs($agent)->post("/admin/withdrawals/{$withdrawal->id}/approve")->assertForbidden();
    }
}
