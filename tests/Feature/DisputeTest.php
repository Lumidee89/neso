<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\EscrowTransaction;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisputeTest extends TestCase
{
    use RefreshDatabase;

    private function fundedContract(): array
    {
        $payer = User::factory()->create(['user_type' => 'payer']);
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        Wallet::create(['user_id' => $payer->id, 'locked_balance' => 5000]);
        Wallet::create(['user_id' => $vendor->id]);
        $contract = Contract::create([
            'payer_id' => $payer->id,
            'vendor_id' => $vendor->id,
            'title' => 'Website redesign',
            'amount' => 5000,
            'status' => 'paid',
        ]);
        $escrow = EscrowTransaction::create([
            'contract_id' => $contract->id,
            'amount' => 5000,
            'status' => 'locked',
            'locked_at' => now(),
        ]);

        return [$payer, $vendor, $contract, $escrow];
    }

    public function test_payer_can_raise_a_dispute_on_a_funded_contract(): void
    {
        [$payer, $vendor, $contract] = $this->fundedContract();

        $response = $this->actingAs($payer, 'sanctum')->postJson('/api/v1/disputes', [
            'contract_id' => $contract->id,
            'reason' => 'Vendor did not deliver as agreed.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('disputes', [
            'contract_id' => $contract->id,
            'raised_by' => $payer->id,
            'status' => 'open',
        ]);
        $this->assertSame('disputed', $contract->escrowTransaction->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $vendor->id,
            'type' => 'dispute_raised',
        ]);
    }

    public function test_unrelated_user_cannot_raise_a_dispute(): void
    {
        [, , $contract] = $this->fundedContract();
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'sanctum')->postJson('/api/v1/disputes', [
            'contract_id' => $contract->id,
            'reason' => 'Not my contract.',
        ])->assertForbidden();
    }

    public function test_cannot_raise_a_dispute_on_an_unfunded_contract(): void
    {
        $payer = User::factory()->create(['user_type' => 'payer']);
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        $contract = Contract::create([
            'payer_id' => $payer->id,
            'vendor_id' => $vendor->id,
            'title' => 'Not funded',
            'amount' => 1000,
            'status' => 'accepted',
        ]);

        $this->actingAs($payer, 'sanctum')->postJson('/api/v1/disputes', [
            'contract_id' => $contract->id,
            'reason' => 'Too early.',
        ])->assertStatus(422);
    }

    public function test_cannot_raise_a_second_open_dispute_on_the_same_contract(): void
    {
        [$payer, , $contract] = $this->fundedContract();

        $this->actingAs($payer, 'sanctum')->postJson('/api/v1/disputes', [
            'contract_id' => $contract->id,
            'reason' => 'First dispute.',
        ])->assertCreated();

        $this->actingAs($payer, 'sanctum')->postJson('/api/v1/disputes', [
            'contract_id' => $contract->id,
            'reason' => 'Second dispute.',
        ])->assertStatus(422);
    }

    public function test_admin_resolving_refund_moves_locked_funds_back_to_payer(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->assignRole('super-admin');

        [$payer, $vendor, $contract] = $this->fundedContract();
        $this->actingAs($payer, 'sanctum')->postJson('/api/v1/disputes', [
            'contract_id' => $contract->id,
            'reason' => 'Refund me please.',
        ])->assertCreated();

        $dispute = $contract->disputes()->first();

        $this->actingAs($admin)->post("/admin/disputes/{$dispute->id}/resolve", [
            'status' => 'resolved_refund',
            'resolution_note' => 'Refunding payer.',
        ])->assertRedirect();

        $this->assertEquals(0, $payer->wallet->fresh()->locked_balance);
        $this->assertEquals(5000, $payer->wallet->fresh()->available_balance);
        $this->assertEquals(0, $vendor->wallet->fresh()->available_balance);
        $this->assertSame('cancelled', $contract->fresh()->status);
        $this->assertSame('refunded', $contract->escrowTransaction->fresh()->status);
    }

    public function test_admin_resolving_release_credits_the_vendor(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->assignRole('super-admin');

        [$payer, $vendor, $contract] = $this->fundedContract();
        $this->actingAs($payer, 'sanctum')->postJson('/api/v1/disputes', [
            'contract_id' => $contract->id,
            'reason' => 'Just checking process.',
        ])->assertCreated();

        $dispute = $contract->disputes()->first();

        $this->actingAs($admin)->post("/admin/disputes/{$dispute->id}/resolve", [
            'status' => 'resolved_release',
            'resolution_note' => 'Releasing to vendor.',
        ])->assertRedirect();

        $this->assertEquals(0, $payer->wallet->fresh()->locked_balance);
        $this->assertEquals(5000, $vendor->wallet->fresh()->available_balance);
        $this->assertSame('completed', $contract->fresh()->status);
        $this->assertSame('released', $contract->escrowTransaction->fresh()->status);
    }
}
