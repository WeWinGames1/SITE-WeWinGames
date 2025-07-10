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
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('league_id')->nullable()->after('sport_id')->constrained()->onDelete('cascade');
            $table->text('description')->nullable()->after('country');
            
            // Add index for faster lookups
            $table->index(['sport_id', 'league_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex(['sport_id', 'league_id', 'name']);
            $table->dropForeign(['league_id']);
            $table->dropColumn(['league_id', 'description']);
        });
    }
};
