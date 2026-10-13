<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\EscrowTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractReleaseTest extends TestCase
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

    public function test_payer_can_release_funds_to_vendor(): void
    {
        [$payer, $vendor, $contract] = $this->fundedContract();

        $response = $this->actingAs($payer, 'sanctum')->postJson("/api/v1/contracts/{$contract->id}/release");

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'completed']);

        $this->assertEquals(0, $payer->wallet->fresh()->locked_balance);
        $this->assertEquals(5000, $vendor->wallet->fresh()->available_balance);
        $this->assertSame('completed', $contract->fresh()->status);
        $this->assertDatabaseHas('escrow_transactions', [
            'contract_id' => $contract->id,
            'status' => 'released',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $vendor->id,
            'type' => 'contract_completed',
        ]);
    }

    public function test_vendor_cannot_release_funds(): void
    {
        [, $vendor, $contract] = $this->fundedContract();

        $this->actingAs($vendor, 'sanctum')
            ->postJson("/api/v1/contracts/{$contract->id}/release")
            ->assertForbidden();
    }

    public function test_cannot_release_a_contract_that_is_not_paid(): void
    {
        $payer = User::factory()->create(['user_type' => 'payer']);
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        $contract = Contract::create([
            'payer_id' => $payer->id,
            'vendor_id' => $vendor->id,
            'title' => 'Not funded yet',
            'amount' => 1000,
            'status' => 'accepted',
        ]);

        $this->actingAs($payer, 'sanctum')
            ->postJson("/api/v1/contracts/{$contract->id}/release")
            ->assertStatus(422);
    }

    public function test_cannot_release_a_disputed_contract(): void
    {
        [$payer, , $contract, $escrow] = $this->fundedContract();
        $escrow->update(['status' => 'disputed']);

        $this->actingAs($payer, 'sanctum')
            ->postJson("/api/v1/contracts/{$contract->id}/release")
            ->assertStatus(422);
    }
}
