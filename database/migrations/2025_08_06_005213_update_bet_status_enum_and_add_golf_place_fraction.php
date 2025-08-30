<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update any 'void' status to 'pending' before changing the enum
        DB::table('bets')->where('status', 'void')->update(['status' => 'pending']);

        // Update the status enum for MySQL
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE bets MODIFY COLUMN status ENUM('pending', 'won', 'loss', 'placed', 'push') DEFAULT 'pending'");
        } else {
            // For SQLite - since it doesn't support ENUM, we just update invalid values
            // No need to recreate the column
        }

        // Add golf_place_fraction column if it doesn't exist
        if (! Schema::hasColumn('bets', 'golf_place_fraction')) {
            Schema::table('bets', function (Blueprint $table) {
                $table->decimal('golf_place_fraction', 8, 4)->nullable()->after('place_fraction');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update any 'placed' or 'push' status back to 'pending' before reverting
        DB::table('bets')->whereIn('status', ['placed', 'push'])->update(['status' => 'pending']);

        // Revert the status enum for MySQL
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE bets MODIFY COLUMN status ENUM('pending', 'won', 'loss', 'void') DEFAULT 'pending'");
        } else {
            // For SQLite - no need to recreate column
        }

        // Remove golf_place_fraction column
        if (Schema::hasColumn('bets', 'golf_place_fraction')) {
            Schema::table('bets', function (Blueprint $table) {
                $table->dropColumn('golf_place_fraction');
            });
        }
    }
};
