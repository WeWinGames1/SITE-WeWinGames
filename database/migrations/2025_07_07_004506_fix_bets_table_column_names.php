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
            // Create sport column if it doesn't exist (instead of renaming for now)
            if (! Schema::hasColumn('bets', 'sport') && Schema::hasColumn('bets', 'sports')) {
                $table->string('sport')->nullable()->after('user_id');
            }

            // Add missing columns
            if (! Schema::hasColumn('bets', 'game')) {
                $table->string('game')->nullable()->after('month');
            }

            if (! Schema::hasColumn('bets', 'bet_type')) {
                $table->string('bet_type')->nullable()->after('game');
            }

            if (! Schema::hasColumn('bets', 'wager_name')) {
                $table->string('wager_name')->nullable()->after('wager_type');
            }

            if (! Schema::hasColumn('bets', 'odds')) {
                $table->decimal('odds', 10, 2)->nullable()->after('wager_name');
            }

            // Remove wager_odds if odds exists
            if (Schema::hasColumn('bets', 'wager_odds') && Schema::hasColumn('bets', 'odds')) {
                $table->dropColumn('wager_odds');
            }

            // Ensure all money fields are decimal with proper precision
            if (Schema::hasColumn('bets', 'wager_amount')) {
                $table->decimal('wager_amount', 10, 2)->nullable()->change();
            }

            if (Schema::hasColumn('bets', 'winning_amount')) {
                $table->decimal('winning_amount', 10, 2)->nullable()->change();
            }

            if (Schema::hasColumn('bets', 'profit_amount')) {
                $table->decimal('profit_amount', 10, 2)->nullable()->change();
            }

            // Ensure ROI is stored properly
            if (! Schema::hasColumn('bets', 'roi_net')) {
                $table->decimal('roi_net', 10, 2)->nullable()->after('roi');
            }

            // Add sport_id and game_id for proper relationships
            if (! Schema::hasColumn('bets', 'sport_id')) {
                $table->foreignId('sport_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('bets', 'game_id')) {
                $table->foreignId('game_id')->nullable()->after('sport_id')->constrained()->nullOnDelete();
            }

            // Index columns for performance (only after rename)
            if (Schema::hasColumn('bets', 'sport')) {
                $table->index('sport', 'bets_sport_idx');
            }
            $table->index('league', 'bets_league_idx');
            $table->index('betting_date', 'bets_betting_date_idx');
            $table->index('status', 'bets_status_idx');
            $table->index('level', 'bets_level_idx');
            $table->index('code', 'bets_code_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('bets_sport_idx');
            $table->dropIndex('bets_league_idx');
            $table->dropIndex('bets_betting_date_idx');
            $table->dropIndex('bets_status_idx');
            $table->dropIndex('bets_level_idx');
            $table->dropIndex('bets_code_idx');

            // Drop foreign keys and columns
            if (Schema::hasColumn('bets', 'sport_id')) {
                $table->dropForeign(['sport_id']);
                $table->dropColumn('sport_id');
            }

            if (Schema::hasColumn('bets', 'game_id')) {
                $table->dropForeign(['game_id']);
                $table->dropColumn('game_id');
            }

            // Drop the sport column if we added it
            if (Schema::hasColumn('bets', 'sport')) {
                $table->dropColumn('sport');
            }

            // Drop added columns
            if (Schema::hasColumn('bets', 'game')) {
                $table->dropColumn('game');
            }

            if (Schema::hasColumn('bets', 'bet_type')) {
                $table->dropColumn('bet_type');
            }

            if (Schema::hasColumn('bets', 'wager_name')) {
                $table->dropColumn('wager_name');
            }

            if (Schema::hasColumn('bets', 'odds')) {
                $table->dropColumn('odds');
            }

            if (Schema::hasColumn('bets', 'roi_net')) {
                $table->dropColumn('roi_net');
            }

            // Restore wager_odds if needed
            if (! Schema::hasColumn('bets', 'wager_odds')) {
                $table->string('wager_odds')->nullable();
            }
        });
    }
};
