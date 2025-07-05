<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProductionUserSeeder extends Seeder
{
    /**
     * Run the database seeds for production admin user.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'subscriber']);
        Role::firstOrCreate(['name' => 'user']);
        
        // Check if admin already exists
        if (User::where('email', 'admin@wewingames.com')->exists()) {
            $this->command->warn('Admin user already exists, skipping...');
            return;
        }
        
        // Create production admin user
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@wewingames.com',
            'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeThisPassword123!')),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole($adminRole);
        
        $this->command->info('Production admin user created:');
        $this->command->info('Email: admin@wewingames.com');
        $this->command->warn('IMPORTANT: Change the admin password immediately after first login!');
        
        if (env('ADMIN_PASSWORD') === null) {
            $this->command->warn('Default password used: ChangeThisPassword123!');
            $this->command->warn('Set ADMIN_PASSWORD in .env for a custom password');
        }
    }
}