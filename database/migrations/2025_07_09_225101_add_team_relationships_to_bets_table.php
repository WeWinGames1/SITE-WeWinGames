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
            // Add foreign keys for teams
            $table->foreignId('team_one_id')->nullable()->after('team_one')->constrained('teams')->nullOnDelete();
            $table->foreignId('team_two_id')->nullable()->after('team_two')->constrained('teams')->nullOnDelete();

            // Add index for faster lookups
            $table->index(['team_one_id', 'team_two_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropIndex(['team_one_id', 'team_two_id']);
            $table->dropForeign(['team_one_id']);
            $table->dropForeign(['team_two_id']);
            $table->dropColumn(['team_one_id', 'team_two_id']);
        });
    }
};
