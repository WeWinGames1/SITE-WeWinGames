<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Run production-safe seeders only.
     */
    public function run(): void
    {
        $this->command->info('Running production seeders...');

        // Essential data that should exist in production
        $this->call([
            // Core system data
            SportSeeder::class,              // Sports categories
            OperatorSeeder::class,           // Betting operators
            TicketCategorySeeder::class,     // Support ticket categories

            // Content
            LegalPagesSeeder::class,         // Legal/compliance pages
            BettingEducationSeeder::class,   // Educational content
            EmailTemplateSeeder::class,      // Email templates

            // Stripe products (essential for subscriptions)
            StripeProductSeeder::class,      // Subscription tiers

            // Security
            SpamEmailDomainsSeeder::class,   // Spam prevention

            // Create initial admin user
            ProductionUserSeeder::class,     // Production admin user
        ]);

        $this->command->info('Production seeding completed!');
    }
}
