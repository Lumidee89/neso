<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@neso.test'],
            [
                'first_name' => 'NESO',
                'last_name' => 'Admin',
                'phone' => '08000000000',
                'password' => Hash::make('password'),
                'user_type' => 'admin',
                'status' => 'verified',
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['super-admin']);
    }
}
