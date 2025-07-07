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
            // Increase wager_type from 50 to 250 characters
            if (Schema::hasColumn('bets', 'wager_type')) {
                $table->string('wager_type', 250)->nullable()->change();
            }
            
            // Increase wager_name to 250 characters
            if (Schema::hasColumn('bets', 'wager_name')) {
                $table->string('wager_name', 250)->nullable()->change();
            }
            
            // Increase game to 250 characters
            if (Schema::hasColumn('bets', 'game')) {
                $table->string('game', 250)->nullable()->change();
            }
            
            // Also increase matches (which stores similar data) to 250 characters
            if (Schema::hasColumn('bets', 'matches')) {
                $table->string('matches', 250)->nullable()->change();
            }
            
            // Increase tips (which can store wager names) to 250 characters
            if (Schema::hasColumn('bets', 'tips')) {
                $table->string('tips', 250)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            // Revert back to original sizes
            if (Schema::hasColumn('bets', 'wager_type')) {
                $table->string('wager_type', 50)->nullable()->change();
            }
            
            if (Schema::hasColumn('bets', 'wager_name')) {
                $table->string('wager_name', 255)->nullable()->change();
            }
            
            if (Schema::hasColumn('bets', 'game')) {
                $table->string('game', 255)->nullable()->change();
            }
            
            if (Schema::hasColumn('bets', 'matches')) {
                $table->string('matches', 255)->nullable()->change();
            }
            
            if (Schema::hasColumn('bets', 'tips')) {
                $table->string('tips', 255)->nullable()->change();
            }
        });
    }
};
