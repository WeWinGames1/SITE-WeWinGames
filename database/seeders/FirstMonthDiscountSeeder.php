<?php

namespace Database\Seeders;

use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Database\Seeder;

class FirstMonthDiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if the discount already exists
        $existingDiscount = DiscountCode::where('code', 'FIRSTMONTH50')->first();

        if ($existingDiscount) {
            $this->command->info('First month 50% discount already exists. Updating...');
            $existingDiscount->update([
                'description' => '50% off your first month on any plan',
                'discount_type' => 'percentage',
                'discount_amount' => 50,
                'apply_to' => 'first_payment',
                'is_active' => true,
                'max_uses' => null, // Unlimited uses
                'max_uses_per_customer' => 1, // One use per customer
                'valid_from' => now(),
                'valid_until' => null, // No expiration
            ]);
        } else {
            // Get the first admin user for created_by
            $adminUser = User::role('admin')->first();

            DiscountCode::create([
                'code' => 'FIRSTMONTH50',
                'description' => '50% off your first month on any plan',
                'discount_type' => 'percentage',
                'discount_amount' => 50,
                'apply_to' => 'first_payment',
                'months_count' => null,
                'max_uses' => null, // Unlimited uses
                'max_uses_per_customer' => 1, // One use per customer
                'times_used' => 0,
                'valid_from' => now(),
                'valid_until' => null, // No expiration
                'applicable_products' => null, // Applies to all products
                'minimum_amount' => null,
                'is_active' => true,
                'stripe_coupon_id' => null, // Will be created when first used
                'created_by' => $adminUser ? $adminUser->id : null,
            ]);

            $this->command->info('Created FIRSTMONTH50 discount code for 50% off first month.');
        }
    }
}
