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
            $table->string('metabet_query_id')->nullable()->after('premium_notes_heading');
            $table->string('metabet_game_name')->nullable()->after('metabet_query_id');
            $table->timestamp('metabet_linked_at')->nullable()->after('metabet_game_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropColumn(['metabet_query_id', 'metabet_game_name', 'metabet_linked_at']);
        });
    }
};
