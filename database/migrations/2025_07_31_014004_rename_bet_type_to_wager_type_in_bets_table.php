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
        // First, copy data from bet_type to wager_type if bet_type has data
        if (Schema::hasColumn('bets', 'bet_type') && Schema::hasColumn('bets', 'wager_type')) {
            DB::statement('UPDATE bets SET wager_type = bet_type WHERE bet_type IS NOT NULL AND (wager_type IS NULL OR wager_type = "")');
        }

        // For production (MySQL), we can safely drop the column
        // For SQLite in development, we'll just leave both columns and rely on the model using wager_type
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('bets', function (Blueprint $table) {
                if (Schema::hasColumn('bets', 'bet_type')) {
                    $table->dropColumn('bet_type');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            // Only add bet_type back if it doesn't exist
            if (! Schema::hasColumn('bets', 'bet_type')) {
                $table->string('bet_type')->nullable()->after('markets');
            }
        });
    }
};
