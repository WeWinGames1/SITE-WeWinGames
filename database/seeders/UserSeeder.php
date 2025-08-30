<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $subscriberRole = Role::firstOrCreate(['name' => 'subscriber']);

        // Create or update admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@wewingames.test'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole($adminRole);

        // Create or reset subscriber user
        $subscriber = User::where('email', 'subscriber@wewingames.test')->first();
        
        if ($subscriber) {
            // Clear existing subscriptions and related data
            $subscriber->subscriptions()->delete();
            $subscriber->discountRedemptions()->delete();
            
            // Reset user data
            $subscriber->update([
                'name' => 'John Subscriber',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'stripe_id' => null,
                'pm_type' => null,
                'pm_last_four' => null,
                'trial_ends_at' => null,
            ]);
        } else {
            // Create new subscriber user
            $subscriber = User::create([
                'name' => 'John Subscriber',
                'email' => 'subscriber@wewingames.test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
        
        $subscriber->assignRole($subscriberRole);

        $this->command->info('Users reset successfully:');
        $this->command->info('Admin: admin@wewingames.test / password');
        $this->command->info('Subscriber: subscriber@wewingames.test / password (cleared for fresh testing)');
    }
}
