<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_payer_can_create_a_contract_for_a_vendor(): void
    {
        $payer = User::factory()->create(['user_type' => 'payer']);
        $vendor = User::factory()->create(['user_type' => 'vendor']);

        $response = $this->actingAs($payer, 'sanctum')->postJson('/api/v1/contracts', [
            'vendor_id' => $vendor->id,
            'title' => 'Website redesign',
            'amount' => 5000,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('contracts', [
            'payer_id' => $payer->id,
            'vendor_id' => $vendor->id,
            'status' => 'sent',
        ]);
    }

    public function test_vendor_cannot_create_a_contract(): void
    {
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        $otherVendor = User::factory()->create(['user_type' => 'vendor']);

        $this->actingAs($vendor, 'sanctum')->postJson('/api/v1/contracts', [
            'vendor_id' => $otherVendor->id,
            'title' => 'Should fail',
            'amount' => 1000,
        ])->assertForbidden();
    }

    public function test_vendor_can_accept_a_contract_sent_to_them(): void
    {
        $payer = User::factory()->create(['user_type' => 'payer']);
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        $contract = Contract::create([
            'payer_id' => $payer->id,
            'vendor_id' => $vendor->id,
            'title' => 'Logo design',
            'amount' => 2000,
            'status' => 'sent',
        ]);

        $this->actingAs($vendor, 'sanctum')
            ->postJson("/api/v1/contracts/{$contract->id}/accept")
            ->assertOk();

        $this->assertSame('accepted', $contract->fresh()->status);
    }

    public function test_funding_moves_balance_from_available_to_locked_and_creates_escrow(): void
    {
        $this->seed(SettingsSeeder::class);

        $payer = User::factory()->create(['user_type' => 'payer']);
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        $wallet = Wallet::create(['user_id' => $payer->id, 'available_balance' => 10000]);
        $contract = Contract::create([
            'payer_id' => $payer->id,
            'vendor_id' => $vendor->id,
            'title' => 'Website redesign',
            'amount' => 5000,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($payer, 'sanctum')->postJson("/api/v1/contracts/{$contract->id}/fund");

        $response->assertOk();

        $wallet->refresh();
        $this->assertEquals(5000, $wallet->available_balance);
        $this->assertEquals(5000, $wallet->locked_balance);

        $this->assertSame('paid', $contract->fresh()->status);
        $this->assertDatabaseHas('escrow_transactions', [
            'contract_id' => $contract->id,
            'amount' => 5000,
            'status' => 'locked',
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'lock',
            'amount' => 5000,
        ]);
    }

    public function test_funding_fails_with_insufficient_balance(): void
    {
        $payer = User::factory()->create(['user_type' => 'payer']);
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        Wallet::create(['user_id' => $payer->id, 'available_balance' => 100]);
        $contract = Contract::create([
            'payer_id' => $payer->id,
            'vendor_id' => $vendor->id,
            'title' => 'Website redesign',
            'amount' => 5000,
            'status' => 'accepted',
        ]);

        $this->actingAs($payer, 'sanctum')
            ->postJson("/api/v1/contracts/{$contract->id}/fund")
            ->assertStatus(422);

        $this->assertSame('accepted', $contract->fresh()->status);
    }

    public function test_vendor_sees_funded_contract_with_escrow_amount(): void
    {
        $this->seed(SettingsSeeder::class);

        $payer = User::factory()->create(['user_type' => 'payer']);
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        Wallet::create(['user_id' => $payer->id, 'available_balance' => 10000]);
        $contract = Contract::create([
            'payer_id' => $payer->id,
            'vendor_id' => $vendor->id,
            'title' => 'Website redesign',
            'amount' => 5000,
            'status' => 'accepted',
        ]);

        $this->actingAs($payer, 'sanctum')->postJson("/api/v1/contracts/{$contract->id}/fund")->assertOk();

        $response = $this->actingAs($vendor, 'sanctum')->getJson('/api/v1/contracts');

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'paid']);
        $this->assertEquals(5000, $response->json('0.escrow_transaction.amount'));
    }
}
