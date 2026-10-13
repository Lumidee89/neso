<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_a_kyc_document_with_a_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/kyc', [
            'document_type' => 'government_id',
            'tier' => 'tier_1',
            'document_number' => 'A1234567',
            'file' => UploadedFile::fake()->image('id.jpg'),
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('kyc_documents', [
            'user_id' => $user->id,
            'document_type' => 'government_id',
            'tier' => 'tier_1',
            'status' => 'pending',
        ]);

        $document = $user->kycDocuments()->first();
        $this->assertNotNull($document->file);
        Storage::disk('local')->assertExists($document->file->path);
    }

    public function test_kyc_submission_requires_a_file(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/kyc', [
            'document_type' => 'government_id',
            'tier' => 'tier_1',
        ])->assertStatus(422);
    }
}
