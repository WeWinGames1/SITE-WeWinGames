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
            $table->boolean('is_parlay')->default(false)->after('bet_type');
            $table->integer('parlay_legs')->default(0)->after('is_parlay');
            
            // Add index for parlay queries
            $table->index('is_parlay');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropIndex(['is_parlay']);
            $table->dropColumn(['is_parlay', 'parlay_legs']);
        });
    }
};