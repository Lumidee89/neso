<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            ServiceChargeSeeder::class,
            SettingsSeeder::class,
        ]);

        $payer = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Payer',
            'email' => 'payer@example.com',
            'phone' => '08000000001',
            'user_type' => 'payer',
            'email_verified_at' => now(),
        ]);

        // Verified (not just email-confirmed) so the seeded account has a wallet
        // and shows the app's full feature set without a manual KYC approval step.
        $payer->update(['status' => 'verified']);
    }
}
