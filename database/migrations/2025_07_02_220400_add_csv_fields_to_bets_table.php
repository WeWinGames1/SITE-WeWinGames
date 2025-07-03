<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            // Add new fields for 16-column CSV format
            if (!Schema::hasColumn('bets', 'wager_type')) {
                $table->string('wager_type', 50)->nullable()->after('markets');
            }
            if (!Schema::hasColumn('bets', 'code')) {
                $table->string('code', 50)->nullable()->after('membership');
            }
            if (!Schema::hasColumn('bets', 'level')) {
                $table->string('level', 50)->nullable()->after('membership');
            }
            if (!Schema::hasColumn('bets', 'month')) {
                $table->string('month', 50)->nullable()->after('league');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropColumn(['wager_type', 'code', 'level', 'month']);
        });
    }
};