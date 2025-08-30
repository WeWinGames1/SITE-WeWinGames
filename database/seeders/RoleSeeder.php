<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $roles = [
            'admin' => 'Administrator with full access',
            'user' => 'Regular user',
            'subscriber' => 'Paid subscriber',
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate(
                ['name' => $roleName],
                ['guard_name' => 'web']
            );
        }

        // Create permissions
        $permissions = [
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'impersonate users',

            // Bet management
            'view bets',
            'create bets',
            'edit bets',
            'delete bets',
            'import bets',
            'export bets',

            // Content management
            'view content',
            'create content',
            'edit content',
            'delete content',

            // Subscription management
            'view subscriptions',
            'manage subscriptions',

            // System settings
            'view settings',
            'edit settings',

            // Admin access
            'access admin panel',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        // Assign all permissions to admin role
        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo(Permission::all());

        // Assign basic permissions to subscriber role
        $subscriberRole = Role::findByName('subscriber');
        $subscriberRole->givePermissionTo([
            'view bets',
            'view content',
        ]);

        // Regular users have no special permissions by default

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
