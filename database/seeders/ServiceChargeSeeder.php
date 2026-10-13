<?php

namespace Database\Seeders;

use App\Models\ServiceCharge;
use Illuminate\Database\Seeder;

class ServiceChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ServiceCharge::firstOrCreate(
            ['name' => 'Standard transaction charge'],
            [
                'type' => 'percentage',
                'value' => 1.00,
                'applies_to' => 'all',
                'active' => true,
            ]
        );

        ServiceCharge::firstOrCreate(
            ['name' => 'Flat withdrawal charge'],
            [
                'type' => 'flat',
                'value' => 500.00,
                'applies_to' => 'withdrawal',
                'active' => true,
            ]
        );
    }
}
