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

        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@wewingames.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole($adminRole);

        // Create subscriber user
        $subscriber = User::create([
            'name' => 'John Subscriber',
            'email' => 'subscriber@wewingames.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $subscriber->assignRole($subscriberRole);

        // Create a subscription for the subscriber
        // Note: This creates a fake subscription for testing
        // In production, you'd use Stripe to create real subscriptions
        $subscriber->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_' . uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => 'price_gold_monthly', // Assuming gold plan
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->command->info('Users created successfully:');
        $this->command->info('Admin: admin@wewingames.test / password');
        $this->command->info('Subscriber: subscriber@wewingames.test / password');
    }
}