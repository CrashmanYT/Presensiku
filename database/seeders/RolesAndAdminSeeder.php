<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure guard
        $guard = 'web';

        // Define permissions
        $permissions = [
            // Logs
            'logs.view',
            'logs.download',
            'logs.manage',
            // Roles & Permissions management
            'roles.view',
            'roles.manage',
            'permissions.view',
            'permissions.manage',
            // Users (optional, for future gating)
            'users.view',
            'users.manage',
        ];

        // Create permissions
        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => $guard,
            ]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
        Role::firstOrCreate(['name' => 'kesiswaan', 'guard_name' => $guard]);
        Role::firstOrCreate(['name' => 'tu', 'guard_name' => $guard]);
        Role::firstOrCreate(['name' => 'guru', 'guard_name' => $guard]);

        // Create an admin user if it doesn't exist
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        )->assignRole($adminRole);

        // Grant all permissions to admin
        $adminRole->syncPermissions(Permission::pluck('name')->all());

        // Clear cached permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
