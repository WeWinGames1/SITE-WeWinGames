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
        // Only run on MySQL - SQLite doesn't have ENUM
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'disabled', 'pending', 'pending_setup') DEFAULT 'active'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // First update any pending_setup users to pending
            DB::table('users')->where('status', 'pending_setup')->update(['status' => 'pending']);

            // Then remove the enum value
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'disabled', 'pending') DEFAULT 'active'");
        }
    }
};
