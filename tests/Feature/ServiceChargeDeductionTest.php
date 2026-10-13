<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ServiceCharge;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceChargeDeductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_funding_deducts_an_active_percentage_charge_that_applies_to_all(): void
    {
        ServiceCharge::create([
            'name' => 'Standard transaction charge',
            'type' => 'percentage',
            'value' => 1.00,
            'applies_to' => 'all',
            'active' => true,
        ]);

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

        // 5000 locked + 50 (1% charge) debited from available.
        $this->assertEquals(4950, $wallet->fresh()->available_balance);
        $this->assertEquals(5000, $wallet->fresh()->locked_balance);

        $this->assertDatabaseHas('charge_transactions', [
            'contract_id' => $contract->id,
            'user_id' => $payer->id,
            'amount' => 50,
            'context' => 'contract_funding',
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'service_charge',
            'amount' => 50,
        ]);
    }

    public function test_charge_not_applying_to_contracts_is_ignored(): void
    {
        ServiceCharge::create([
            'name' => 'Flat withdrawal charge',
            'type' => 'flat',
            'value' => 500,
            'applies_to' => 'withdrawal',
            'active' => true,
        ]);

        $payer = User::factory()->create(['user_type' => 'payer']);
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        $wallet = Wallet::create(['user_id' => $payer->id, 'available_balance' => 5000]);
        $contract = Contract::create([
            'payer_id' => $payer->id,
            'vendor_id' => $vendor->id,
            'title' => 'Website redesign',
            'amount' => 5000,
            'status' => 'accepted',
        ]);

        $this->actingAs($payer, 'sanctum')->postJson("/api/v1/contracts/{$contract->id}/fund")->assertOk();

        $this->assertEquals(0, $wallet->fresh()->available_balance);
        $this->assertDatabaseMissing('charge_transactions', ['contract_id' => $contract->id]);
    }

    public function test_funding_fails_when_balance_covers_amount_but_not_the_charge(): void
    {
        ServiceCharge::create([
            'name' => 'Standard transaction charge',
            'type' => 'percentage',
            'value' => 1.00,
            'applies_to' => 'all',
            'active' => true,
        ]);

        $payer = User::factory()->create(['user_type' => 'payer']);
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        Wallet::create(['user_id' => $payer->id, 'available_balance' => 5000]);
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

    public function test_inactive_charge_is_not_applied(): void
    {
        ServiceCharge::create([
            'name' => 'Standard transaction charge',
            'type' => 'percentage',
            'value' => 1.00,
            'applies_to' => 'all',
            'active' => false,
        ]);

        $payer = User::factory()->create(['user_type' => 'payer']);
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        $wallet = Wallet::create(['user_id' => $payer->id, 'available_balance' => 5000]);
        $contract = Contract::create([
            'payer_id' => $payer->id,
            'vendor_id' => $vendor->id,
            'title' => 'Website redesign',
            'amount' => 5000,
            'status' => 'accepted',
        ]);

        $this->actingAs($payer, 'sanctum')->postJson("/api/v1/contracts/{$contract->id}/fund")->assertOk();

        $this->assertEquals(0, $wallet->fresh()->available_balance);
        $this->assertDatabaseMissing('charge_transactions', ['contract_id' => $contract->id]);
    }
}
