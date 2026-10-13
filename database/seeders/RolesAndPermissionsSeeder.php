<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'approve-kyc',
            'freeze-accounts',
            'manage-disputes',
            'view-transactions',
            'configure-charges',
            'manage-wallets',
            'view-reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'super-admin' => $permissions,
            'compliance-officer' => ['approve-kyc', 'freeze-accounts', 'manage-disputes', 'view-transactions'],
            'support-agent' => ['view-transactions', 'view-reports'],
        ];

        foreach ($roles as $role => $rolePermissions) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])
                ->syncPermissions($rolePermissions);
        }
    }
}
