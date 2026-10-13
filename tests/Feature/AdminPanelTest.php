<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Dispute;
use App\Models\File;
use App\Models\KycDocument;
use App\Models\ServiceCharge;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(string $role = 'super-admin'): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create(['user_type' => 'admin', 'status' => 'verified']);
        $admin->assignRole($role);

        return $admin;
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_can_view_dashboard(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Dashboard'));
    }

    public function test_approving_last_kyc_document_verifies_user_and_creates_wallet(): void
    {
        $admin = $this->makeAdmin();
        $applicant = User::factory()->create();
        $kyc = KycDocument::create([
            'user_id' => $applicant->id,
            'document_type' => 'nin',
            'tier' => 'tier_2',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/admin/kyc/{$kyc->id}/approve")
            ->assertRedirect();

        $this->assertSame('approved', $kyc->fresh()->status);
        $this->assertSame('verified', $applicant->fresh()->status);
        $this->assertNotNull($applicant->fresh()->wallet);
    }

    public function test_admin_can_view_an_uploaded_kyc_document(): void
    {
        Storage::fake('local');
        $admin = $this->makeAdmin();
        $applicant = User::factory()->create();

        $file = File::create([
            'path' => UploadedFile::fake()->image('id.jpg')->store('kyc/'.$applicant->id, 'local'),
            'original_name' => 'id.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
            'uploaded_by' => $applicant->id,
        ]);
        $kyc = KycDocument::create([
            'user_id' => $applicant->id,
            'document_type' => 'nin',
            'tier' => 'tier_2',
            'file_id' => $file->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get("/admin/kyc/{$kyc->id}/document")
            ->assertOk();
    }

    public function test_support_agent_cannot_freeze_wallets(): void
    {
        $agent = $this->makeAdmin('support-agent');
        $owner = User::factory()->create(['status' => 'verified']);
        $wallet = Wallet::create(['user_id' => $owner->id]);

        $this->actingAs($agent)
            ->post("/admin/wallets/{$wallet->id}/freeze")
            ->assertForbidden();

        $this->assertSame('verified', $owner->fresh()->status);
    }

    public function test_super_admin_can_freeze_and_unfreeze_wallet_owner(): void
    {
        $admin = $this->makeAdmin();
        $owner = User::factory()->create(['status' => 'verified']);
        $wallet = Wallet::create(['user_id' => $owner->id]);

        $this->actingAs($admin)->post("/admin/wallets/{$wallet->id}/freeze")->assertRedirect();
        $this->assertSame('suspended', $owner->fresh()->status);

        $this->actingAs($admin)->post("/admin/wallets/{$wallet->id}/unfreeze")->assertRedirect();
        $this->assertSame('verified', $owner->fresh()->status);
    }

    public function test_admin_can_resolve_a_dispute(): void
    {
        $admin = $this->makeAdmin();
        $payer = User::factory()->create();
        $vendor = User::factory()->create();
        $contract = Contract::create([
            'payer_id' => $payer->id,
            'vendor_id' => $vendor->id,
            'title' => 'Test contract',
            'amount' => 1000,
            'status' => 'paid',
        ]);
        $dispute = Dispute::create([
            'contract_id' => $contract->id,
            'raised_by' => $payer->id,
            'reason' => 'Not delivered',
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->post("/admin/disputes/{$dispute->id}/resolve", [
                'status' => 'resolved_refund',
                'resolution_note' => 'Refunding payer',
            ])
            ->assertRedirect();

        $dispute->refresh();
        $this->assertSame('resolved_refund', $dispute->status);
        $this->assertSame($admin->id, $dispute->resolved_by);
    }

    public function test_admin_can_create_and_toggle_a_service_charge(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/admin/service-charges', [
            'name' => 'Test charge',
            'type' => 'flat',
            'value' => 100,
            'applies_to' => 'all',
        ])->assertRedirect();

        $charge = ServiceCharge::where('name', 'Test charge')->firstOrFail();
        $this->assertTrue($charge->active);

        $this->actingAs($admin)->post("/admin/service-charges/{$charge->id}/toggle")->assertRedirect();
        $this->assertFalse($charge->fresh()->active);
    }
}
