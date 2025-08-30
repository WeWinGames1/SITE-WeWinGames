<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all bets with status 'lost' to 'loss'
        DB::table('bets')
            ->where('status', 'lost')
            ->update(['status' => 'loss']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert all bets with status 'loss' back to 'lost'
        DB::table('bets')
            ->where('status', 'loss')
            ->update(['status' => 'lost']);
    }
};
