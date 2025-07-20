<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ProductionRoleCheckSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder is safe to run in production as it only creates missing roles.
     */
    public function run(): void
    {
        $this->command->info('Checking for required roles...');
        
        $requiredRoles = ['admin', 'user', 'subscriber'];
        $createdCount = 0;
        
        foreach ($requiredRoles as $roleName) {
            if (!Role::where('name', $roleName)->exists()) {
                Role::create(['name' => $roleName, 'guard_name' => 'web']);
                $this->command->info("Created missing role: {$roleName}");
                $createdCount++;
            } else {
                $this->command->info("Role already exists: {$roleName}");
            }
        }
        
        if ($createdCount > 0) {
            $this->command->info("Created {$createdCount} missing role(s).");
        } else {
            $this->command->info('All required roles already exist.');
        }
        
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
