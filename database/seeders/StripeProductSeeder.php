<?php

namespace Database\Seeders;

use App\Models\StripeProduct;
use Illuminate\Database\Seeder;

class StripeProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Deactivate all Silver tier products (no longer offered)
        \App\Models\StripeProduct::where('tier', 'Silver')->update(['is_active' => false]);

        $products = [
            // Gold Plans - $45/month
            [
                'name' => 'Gold Monthly',
                'tier' => 'Gold',
                'billing_period' => 'monthly',
                'price' => 45.00,
                'features' => [
                    'All Free picks included',
                    'Access to Gold-tier premium picks',
                    'Priority email notifications',
                    'Advanced analytics',
                    'Early access to picks',
                    'Monthly performance reports',
                ],
                'badge_text' => 'Most Popular',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Gold Weekly',
                'tier' => 'Gold',
                'billing_period' => 'weekly',
                'price' => 15.00,
                'features' => [
                    'All Free picks included',
                    'Access to Gold-tier premium picks',
                    'Priority email notifications',
                    'Advanced analytics',
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Gold Daily',
                'tier' => 'Gold',
                'billing_period' => 'daily',
                'price' => 5.00,
                'features' => [
                    'All Free picks included',
                    'Access to Gold-tier premium picks',
                    'Priority email notifications',
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],

            // Platinum Plans - $90/month
            [
                'name' => 'Platinum Monthly',
                'tier' => 'Platinum',
                'billing_period' => 'monthly',
                'price' => 90.00,
                'features' => [
                    'All Free and Gold picks included',
                    'Access to ALL premium picks',
                    'Instant notifications',
                    'Premium analytics dashboard',
                    'VIP support',
                    'Exclusive insider tips',
                    'Personal betting consultant',
                    'Custom betting strategies',
                ],
                'badge_text' => 'Best Value',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Platinum Weekly',
                'tier' => 'Platinum',
                'billing_period' => 'weekly',
                'price' => 30.00,
                'features' => [
                    'All Free and Gold picks included',
                    'Access to ALL premium picks',
                    'Instant notifications',
                    'Premium analytics',
                    'VIP support',
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Platinum Daily',
                'tier' => 'Platinum',
                'billing_period' => 'daily',
                'price' => 10.00,
                'features' => [
                    'All Free and Gold picks included',
                    'Access to ALL premium picks',
                    'Instant notifications',
                    'VIP support',
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            StripeProduct::updateOrCreate(
                [
                    'tier' => $product['tier'],
                    'billing_period' => $product['billing_period'],
                ],
                $product
            );
        }

        $this->command->info('Stripe products seeded successfully!');
    }
}
