<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need a different approach since it doesn't support ENUM modifications
        if (config('database.default') === 'sqlite') {
            // SQLite doesn't have ENUMs, so the column is actually just a string
            // We don't need to do anything for SQLite
            return;
        }

        // For MySQL and PostgreSQL
        DB::statement("ALTER TABLE stripe_products MODIFY COLUMN billing_period ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            // Nothing to do for SQLite
            return;
        }

        // First update any yearly to monthly
        DB::statement("UPDATE stripe_products SET billing_period = 'monthly' WHERE billing_period = 'yearly'");
        // Then modify the column
        DB::statement("ALTER TABLE stripe_products MODIFY COLUMN billing_period ENUM('daily', 'weekly', 'monthly') NOT NULL");
    }
};
