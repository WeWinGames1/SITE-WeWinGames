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
            // Position and place determination fields
            $table->string('finishing_position', 20)->nullable()->after('status')
                ->comment('e.g., T5, 2nd, MC, WD');
            $table->integer('places_paid')->nullable()->after('finishing_position')
                ->comment('Number of places that pay (6, 8, 10, etc.)');
            $table->integer('position_numeric')->nullable()->after('places_paid')
                ->comment('Numeric position for calculations (5 for T5)');
            
            // Dead heat fields
            $table->boolean('is_dead_heat')->default(false)->after('place_payout');
            $table->integer('dead_heat_players')->nullable()->after('is_dead_heat')
                ->comment('Number of players tied');
            $table->decimal('dead_heat_spots', 10, 4)->nullable()->after('dead_heat_players')
                ->comment('Available spots (can be fractional)');
            
            // Enhanced result tracking
            $table->enum('bet_result_type', [
                'won_outright', 
                'placed', 
                'placed_dead_heat', 
                'lost', 
                'void'
            ])->nullable()->after('status');
            
            // Additional Each Way fields
            $table->integer('place_terms_denominator')->default(5)->after('place_fraction')
                ->comment('For 1/5, store 5');
            
            // Add indexes for better query performance
            $table->index('finishing_position', 'idx_finishing_position');
            $table->index('bet_result_type', 'idx_bet_result_type');
            $table->index(['is_each_way', 'bet_result_type'], 'idx_each_way_result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_finishing_position');
            $table->dropIndex('idx_bet_result_type');
            $table->dropIndex('idx_each_way_result');
            
            // Drop columns
            $table->dropColumn([
                'finishing_position',
                'places_paid',
                'position_numeric',
                'is_dead_heat',
                'dead_heat_players',
                'dead_heat_spots',
                'bet_result_type',
                'place_terms_denominator'
            ]);
        });
    }
};