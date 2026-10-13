<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletFundingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_fund_their_wallet(): void
    {
        $user = User::factory()->create(['user_type' => 'payer']);
        $wallet = Wallet::create(['user_id' => $user->id, 'available_balance' => 1000]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/wallet/fund', [
            'amount' => 2500,
        ]);

        $response->assertCreated();

        $this->assertEquals(3500, $wallet->fresh()->available_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => 2500,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'wallet_funded',
        ]);
    }

    public function test_funding_requires_an_existing_wallet(): void
    {
        $user = User::factory()->create(['user_type' => 'payer']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/wallet/fund', ['amount' => 1000])
            ->assertStatus(422);
    }

    public function test_funding_requires_a_positive_amount(): void
    {
        $user = User::factory()->create(['user_type' => 'payer']);
        Wallet::create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/wallet/fund', ['amount' => 0])
            ->assertStatus(422);
    }
}
