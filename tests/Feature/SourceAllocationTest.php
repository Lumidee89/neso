<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\EscrowTransaction;
use App\Models\SourceAllocation;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceAllocationTest extends TestCase
{
    use RefreshDatabase;

    private function fundedContract(float $amount = 10000): array
    {
        $payer = User::factory()->create(['user_type' => 'payer']);
        $vendor = User::factory()->create(['user_type' => 'vendor']);
        $source = User::factory()->create(['user_type' => 'source']);
        Wallet::create(['user_id' => $payer->id, 'locked_balance' => $amount]);
        Wallet::create(['user_id' => $vendor->id]);
        Wallet::create(['user_id' => $source->id]);
        $contract = Contract::create([
            'payer_id' => $payer->id,
            'vendor_id' => $vendor->id,
            'title' => 'Website redesign',
            'amount' => $amount,
            'status' => 'paid',
        ]);
        $escrow = EscrowTransaction::create([
            'contract_id' => $contract->id,
            'amount' => $amount,
            'status' => 'locked',
            'locked_at' => now(),
        ]);

        return [$payer, $vendor, $source, $contract, $escrow];
    }

    public function test_vendor_can_allocate_part_of_the_locked_amount_to_a_source(): void
    {
        [, $vendor, $source, $contract] = $this->fundedContract();

        $response = $this->actingAs($vendor, 'sanctum')->postJson('/api/v1/source-allocations', [
            'contract_id' => $contract->id,
            'source_id' => $source->id,
            'amount' => 3000,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('source_allocations', [
            'contract_id' => $contract->id,
            'vendor_id' => $vendor->id,
            'source_id' => $source->id,
            'allocated_amount' => 3000,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('notifications', ['user_id' => $source->id, 'type' => 'allocation_proposed']);
    }

    public function test_cannot_allocate_more_than_the_remaining_locked_amount(): void
    {
        [, $vendor, $source, $contract] = $this->fundedContract(10000);

        $this->actingAs($vendor, 'sanctum')->postJson('/api/v1/source-allocations', [
            'contract_id' => $contract->id,
            'source_id' => $source->id,
            'amount' => 6000,
        ])->assertCreated();

        // Only 4000 remains unallocated; requesting 5000 more should fail.
        $this->actingAs($vendor, 'sanctum')->postJson('/api/v1/source-allocations', [
            'contract_id' => $contract->id,
            'source_id' => $source->id,
            'amount' => 5000,
        ])->assertStatus(422);
    }

    public function test_payer_cannot_create_a_source_allocation(): void
    {
        [$payer, , $source, $contract] = $this->fundedContract();

        $this->actingAs($payer, 'sanctum')->postJson('/api/v1/source-allocations', [
            'contract_id' => $contract->id,
            'source_id' => $source->id,
            'amount' => 1000,
        ])->assertForbidden();
    }

    public function test_vendor_of_another_contract_cannot_allocate(): void
    {
        [, , $source, $contract] = $this->fundedContract();
        $otherVendor = User::factory()->create(['user_type' => 'vendor']);

        $this->actingAs($otherVendor, 'sanctum')->postJson('/api/v1/source-allocations', [
            'contract_id' => $contract->id,
            'source_id' => $source->id,
            'amount' => 1000,
        ])->assertForbidden();
    }

    public function test_source_sees_allocated_amount_but_not_the_contract_amount(): void
    {
        [, $vendor, $source, $contract] = $this->fundedContract(10000);
        $this->actingAs($vendor, 'sanctum')->postJson('/api/v1/source-allocations', [
            'contract_id' => $contract->id,
            'source_id' => $source->id,
            'amount' => 3000,
        ])->assertCreated();

        $response = $this->actingAs($source, 'sanctum')->getJson('/api/v1/source-allocations');

        $response->assertOk();
        $body = $response->json();
        $this->assertEquals(3000, $body[0]['allocated_amount']);
        $this->assertArrayNotHasKey('amount', $body[0]['contract']);
        $this->assertSame($contract->title, $body[0]['contract']['title']);
    }

    public function test_vendor_sees_the_full_contract_amount(): void
    {
        [, $vendor, $source, $contract] = $this->fundedContract(10000);
        $this->actingAs($vendor, 'sanctum')->postJson('/api/v1/source-allocations', [
            'contract_id' => $contract->id,
            'source_id' => $source->id,
            'amount' => 3000,
        ])->assertCreated();

        $response = $this->actingAs($vendor, 'sanctum')->getJson('/api/v1/source-allocations');

        $response->assertOk();
        $this->assertEquals(10000, $response->json('0.contract.amount'));
    }

    public function test_source_can_accept_and_reject_allocations(): void
    {
        [, $vendor, $source, $contract] = $this->fundedContract();
        $allocation = SourceAllocation::create([
            'contract_id' => $contract->id,
            'vendor_id' => $vendor->id,
            'source_id' => $source->id,
            'allocated_amount' => 3000,
            'transaction_code' => 'ALLOC-TEST-1',
            'status' => 'pending',
        ]);

        $this->actingAs($source, 'sanctum')
            ->postJson("/api/v1/source-allocations/{$allocation->id}/accept")
            ->assertOk();

        $this->assertSame('approved', $allocation->fresh()->status);
        $this->assertDatabaseHas('notifications', ['user_id' => $vendor->id, 'type' => 'allocation_accepted']);
    }

    public function test_vendor_cannot_accept_their_own_allocation(): void
    {
        [, $vendor, , $contract] = $this->fundedContract();
        $allocation = SourceAllocation::create([
            'contract_id' => $contract->id,
            'vendor_id' => $vendor->id,
            'source_id' => User::factory()->create(['user_type' => 'source'])->id,
            'allocated_amount' => 3000,
            'transaction_code' => 'ALLOC-TEST-2',
            'status' => 'pending',
        ]);

        $this->actingAs($vendor, 'sanctum')
            ->postJson("/api/v1/source-allocations/{$allocation->id}/accept")
            ->assertForbidden();
    }

    public function test_release_splits_payout_between_vendor_and_approved_source(): void
    {
        [$payer, $vendor, $source, $contract] = $this->fundedContract(10000);
        $allocation = SourceAllocation::create([
            'contract_id' => $contract->id,
            'vendor_id' => $vendor->id,
            'source_id' => $source->id,
            'allocated_amount' => 3000,
            'transaction_code' => 'ALLOC-TEST-3',
            'status' => 'approved',
        ]);

        $this->actingAs($payer, 'sanctum')
            ->postJson("/api/v1/contracts/{$contract->id}/release")
            ->assertOk();

        $this->assertEquals(0, $payer->wallet->fresh()->locked_balance);
        $this->assertEquals(7000, $vendor->wallet->fresh()->available_balance);
        $this->assertEquals(3000, $source->wallet->fresh()->available_balance);
        $this->assertSame('released', $allocation->fresh()->status);
        $this->assertSame('completed', $contract->fresh()->status);
        $this->assertDatabaseHas('notifications', ['user_id' => $source->id, 'type' => 'allocation_released']);
    }

    public function test_release_auto_rejects_a_still_pending_allocation(): void
    {
        [$payer, $vendor, $source, $contract] = $this->fundedContract(10000);
        $allocation = SourceAllocation::create([
            'contract_id' => $contract->id,
            'vendor_id' => $vendor->id,
            'source_id' => $source->id,
            'allocated_amount' => 3000,
            'transaction_code' => 'ALLOC-TEST-4',
            'status' => 'pending',
        ]);

        $this->actingAs($payer, 'sanctum')->postJson("/api/v1/contracts/{$contract->id}/release")->assertOk();

        $this->assertSame('rejected', $allocation->fresh()->status);
        $this->assertEquals(10000, $vendor->wallet->fresh()->available_balance);
        $this->assertEquals(0, $source->wallet->fresh()->available_balance);
    }

    public function test_dispute_refund_voids_approved_allocations(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->assignRole('super-admin');

        [$payer, $vendor, $source, $contract, $escrow] = $this->fundedContract(10000);
        $allocation = SourceAllocation::create([
            'contract_id' => $contract->id,
            'vendor_id' => $vendor->id,
            'source_id' => $source->id,
            'allocated_amount' => 3000,
            'transaction_code' => 'ALLOC-TEST-5',
            'status' => 'approved',
        ]);
        $escrow->update(['status' => 'disputed']);

        $dispute = \App\Models\Dispute::create([
            'contract_id' => $contract->id,
            'raised_by' => $payer->id,
            'reason' => 'Not delivered',
            'status' => 'open',
        ]);

        $this->actingAs($admin)->post("/admin/disputes/{$dispute->id}/resolve", [
            'status' => 'resolved_refund',
            'resolution_note' => 'Refunding payer.',
        ])->assertRedirect();

        $this->assertEquals(10000, $payer->wallet->fresh()->available_balance);
        $this->assertEquals(0, $source->wallet->fresh()->available_balance);
        $this->assertSame('rejected', $allocation->fresh()->status);
    }

    public function test_dispute_release_splits_between_vendor_and_source(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->assignRole('super-admin');

        [$payer, $vendor, $source, $contract, $escrow] = $this->fundedContract(10000);
        $allocation = SourceAllocation::create([
            'contract_id' => $contract->id,
            'vendor_id' => $vendor->id,
            'source_id' => $source->id,
            'allocated_amount' => 3000,
            'transaction_code' => 'ALLOC-TEST-6',
            'status' => 'approved',
        ]);
        $escrow->update(['status' => 'disputed']);

        $dispute = \App\Models\Dispute::create([
            'contract_id' => $contract->id,
            'raised_by' => $payer->id,
            'reason' => 'Checking process',
            'status' => 'open',
        ]);

        $this->actingAs($admin)->post("/admin/disputes/{$dispute->id}/resolve", [
            'status' => 'resolved_release',
            'resolution_note' => 'Releasing.',
        ])->assertRedirect();

        $this->assertEquals(7000, $vendor->wallet->fresh()->available_balance);
        $this->assertEquals(3000, $source->wallet->fresh()->available_balance);
        $this->assertSame('released', $allocation->fresh()->status);
    }
}
