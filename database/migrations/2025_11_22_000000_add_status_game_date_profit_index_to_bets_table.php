<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            // Add composite index for covering query: select game_date, profit_amount from bets where status in (...)
            $table->index(['status', 'game_date', 'profit_amount'], 'bets_status_game_date_profit_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropIndex('bets_status_game_date_profit_index');
        });
    }
};
