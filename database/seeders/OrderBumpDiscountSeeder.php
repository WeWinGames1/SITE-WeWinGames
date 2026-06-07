<?php

namespace Database\Seeders;

use App\Models\DiscountCode;
use Illuminate\Database\Seeder;

class OrderBumpDiscountSeeder extends Seeder
{
    /**
     * Seed the DAILYUPGRADE10 discount code for order bump modal.
     * This discount is offered when users select Daily plan to encourage Weekly upgrade.
     */
    public function run(): void
    {
        DiscountCode::updateOrCreate(
            ['code' => 'DAILYUPGRADE10'],
            [
                'description' => 'Order bump discount - $10 off Weekly when upgrading from Daily selection',
                'discount_type' => 'fixed',
                'discount_amount' => 10.00,
                'apply_to' => 'first_payment',
                'months_count' => null,
                'max_uses' => null,
                'max_uses_per_customer' => 1,
                'times_used' => 0,
                'valid_from' => null,
                'valid_until' => null,
                'applicable_products' => null,
                'minimum_amount' => null,
                'is_active' => true,
                'stripe_coupon_id' => null,
            ]
        );

        $this->command->info('Created DAILYUPGRADE10 discount code for order bump modal.');
    }
}
