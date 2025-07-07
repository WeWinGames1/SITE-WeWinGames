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
        $products = [
            // Silver Plans
            [
                'name' => 'Silver Monthly',
                'tier' => 'Silver',
                'billing_period' => 'monthly',
                'price' => 60.00,
                'features' => [
                    'Access to basic picks',
                    'Email notifications',
                    'Basic analytics',
                    'Community access',
                ],
                'sort_order' => 1,
            ],
            [
                'name' => 'Silver Weekly',
                'tier' => 'Silver',
                'billing_period' => 'weekly',
                'price' => 20.00,
                'features' => [
                    'Access to basic picks',
                    'Email notifications',
                    'Basic analytics',
                    'Community access',
                ],
                'sort_order' => 1,
            ],
            [
                'name' => 'Silver Daily',
                'tier' => 'Silver',
                'billing_period' => 'daily',
                'price' => 5.00,
                'features' => [
                    'Access to basic picks',
                    'Email notifications',
                    'Basic analytics',
                ],
                'sort_order' => 1,
            ],

            // Gold Plans
            [
                'name' => 'Gold Monthly',
                'tier' => 'Gold',
                'billing_period' => 'monthly',
                'price' => 110.00,
                'features' => [
                    'Access to all picks',
                    'Priority email notifications',
                    'Advanced analytics',
                    'Early access to picks',
                    'Monthly performance reports',
                ],
                'badge_text' => 'Best Value',
                'sort_order' => 2,
            ],
            [
                'name' => 'Gold Weekly',
                'tier' => 'Gold',
                'billing_period' => 'weekly',
                'price' => 39.00,
                'features' => [
                    'Access to all picks',
                    'Priority email notifications',
                    'Advanced analytics',
                    'Early access to picks',
                ],
                'badge_text' => 'Popular',
                'sort_order' => 2,
            ],
            [
                'name' => 'Gold Daily',
                'tier' => 'Gold',
                'billing_period' => 'daily',
                'price' => 10.00,
                'features' => [
                    'Access to all picks',
                    'Priority email notifications',
                    'Advanced analytics',
                ],
                'sort_order' => 2,
            ],

            // Platinum Plans
            [
                'name' => 'Platinum Monthly',
                'tier' => 'Platinum',
                'billing_period' => 'monthly',
                'price' => 149.00,
                'features' => [
                    'Access to all picks',
                    'Instant notifications',
                    'Premium analytics',
                    'VIP support',
                    'Exclusive insider tips',
                    'Personal betting consultant',
                    'Custom betting strategies',
                ],
                'sort_order' => 3,
            ],
            [
                'name' => 'Platinum Weekly',
                'tier' => 'Platinum',
                'billing_period' => 'weekly',
                'price' => 49.00,
                'features' => [
                    'Access to all picks',
                    'Instant notifications',
                    'Premium analytics',
                    'VIP support',
                    'Exclusive insider tips',
                ],
                'sort_order' => 3,
            ],
            [
                'name' => 'Platinum Daily',
                'tier' => 'Platinum',
                'billing_period' => 'daily',
                'price' => 12.00,
                'features' => [
                    'Access to all picks',
                    'Instant notifications',
                    'Premium analytics',
                    'VIP support',
                ],
                'sort_order' => 3,
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
