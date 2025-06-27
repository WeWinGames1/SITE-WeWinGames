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
            //
            $table->string('sports');
            $table->string('league');
            $table->string('matches');
            $table->string('markets');
            $table->string('team_one');
            $table->string('team_two');
            $table->string('tips');
            $table->date('betting_date');
            $table->string('wager_odds');
            $table->string('membership');
            $table->decimal('wager_amount', 10, 2)->nullable()->after('markets');
            $table->decimal('winning_amount', 10, 2)->nullable()->after('wager_amount');
            $table->decimal('profit_amount', 10, 2)->nullable()->after('winning_amount');
            $table->decimal('roi', 10, 2)->nullable()->after('profit_amount');
            $table->string('status')->default('Pending')->after('roi');
            $table->string('tips_status')->default('Activate')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            //
        });
    }
};
