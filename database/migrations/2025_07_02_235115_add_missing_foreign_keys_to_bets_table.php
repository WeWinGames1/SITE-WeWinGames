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
            // Add missing foreign key columns only if they don't exist
            if (! Schema::hasColumn('bets', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('bets', 'sport_id')) {
                $table->foreignId('sport_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('bets', 'game_id')) {
                $table->foreignId('game_id')->nullable()->after('sport_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('bets', 'operator_id')) {
                $table->foreignId('operator_id')->nullable()->after('game_id')->constrained()->nullOnDelete();
            }

            // Add game_at column if it doesn't exist
            if (! Schema::hasColumn('bets', 'game_at')) {
                $table->timestamp('game_at')->nullable()->after('betting_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            // Drop foreign key constraints if they exist
            if (Schema::hasColumn('bets', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('bets', 'sport_id')) {
                $table->dropForeign(['sport_id']);
                $table->dropColumn('sport_id');
            }
            if (Schema::hasColumn('bets', 'game_id')) {
                $table->dropForeign(['game_id']);
                $table->dropColumn('game_id');
            }
            if (Schema::hasColumn('bets', 'operator_id')) {
                $table->dropForeign(['operator_id']);
                $table->dropColumn('operator_id');
            }

            if (Schema::hasColumn('bets', 'game_at')) {
                $table->dropColumn('game_at');
            }
        });
    }
};
