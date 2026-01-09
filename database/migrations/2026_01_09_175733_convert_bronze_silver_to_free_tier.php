<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Convert all Bronze and Silver tier picks to Free tier.
     * This is part of the tier restructuring where:
     * - Bronze/Silver become "Free" (accessible to all logged-in users)
     * - Gold remains at $45/month
     * - Platinum remains at $90/month
     */
    public function up(): void
    {
        // Update all Bronze picks to Free
        DB::table('bets')
            ->where('membership', 'Bronze')
            ->update(['membership' => 'Free']);

        // Update all Silver picks to Free
        DB::table('bets')
            ->where('membership', 'Silver')
            ->update(['membership' => 'Free']);

        // Also update the level field if it contains Bronze or Silver
        DB::table('bets')
            ->where('level', 'Bronze')
            ->update(['level' => 'Free']);

        DB::table('bets')
            ->where('level', 'Silver')
            ->update(['level' => 'Free']);

        // Deactivate Silver tier products in stripe_products table
        DB::table('stripe_products')
            ->where('tier', 'Silver')
            ->update(['is_active' => false]);

        // Update Gold monthly price to $45
        DB::table('stripe_products')
            ->where('tier', 'Gold')
            ->where('billing_period', 'monthly')
            ->update(['price' => 45.00]);

        // Update Platinum monthly price to $90
        DB::table('stripe_products')
            ->where('tier', 'Platinum')
            ->where('billing_period', 'monthly')
            ->update(['price' => 90.00]);
    }

    /**
     * Reverse the migrations.
     *
     * Note: This cannot fully restore the original Bronze/Silver distinction
     * as that information is lost. All Free picks will be reverted to Bronze.
     */
    public function down(): void
    {
        // Revert Free picks back to Bronze (cannot distinguish original Bronze vs Silver)
        DB::table('bets')
            ->where('membership', 'Free')
            ->update(['membership' => 'Bronze']);

        DB::table('bets')
            ->where('level', 'Free')
            ->update(['level' => 'Bronze']);

        // Reactivate Silver tier products
        DB::table('stripe_products')
            ->where('tier', 'Silver')
            ->update(['is_active' => true]);

        // Revert Gold monthly price
        DB::table('stripe_products')
            ->where('tier', 'Gold')
            ->where('billing_period', 'monthly')
            ->update(['price' => 130.00]);

        // Revert Platinum monthly price
        DB::table('stripe_products')
            ->where('tier', 'Platinum')
            ->where('billing_period', 'monthly')
            ->update(['price' => 160.00]);
    }
};
