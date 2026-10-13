<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::firstOrCreate(
            ['key' => 'escrow_release_delay_seconds'],
            ['value' => 3600, 'group' => 'escrow']
        );

        Setting::firstOrCreate(
            ['key' => 'otp_expiry_minutes'],
            ['value' => 10, 'group' => 'auth']
        );
    }
}
