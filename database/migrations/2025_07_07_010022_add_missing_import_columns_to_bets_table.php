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
            // Add game_date as an alias for betting_date
            if (!Schema::hasColumn('bets', 'game_date')) {
                $table->dateTime('game_date')->nullable()->after('betting_date');
            }
            
            // Add profits as an alias for profit_amount
            if (!Schema::hasColumn('bets', 'profits')) {
                $table->decimal('profits', 10, 2)->nullable()->after('profit_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            if (Schema::hasColumn('bets', 'game_date')) {
                $table->dropColumn('game_date');
            }
            
            if (Schema::hasColumn('bets', 'profits')) {
                $table->dropColumn('profits');
            }
        });
    }
};
