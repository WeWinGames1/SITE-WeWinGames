<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change the default value of premium_notes_enabled to false
        Schema::table('bets', function (Blueprint $table) {
            $table->boolean('premium_notes_enabled')->default(false)->change();
        });

        // Update existing records that have null to be false
        DB::table('bets')
            ->whereNull('premium_notes_enabled')
            ->update(['premium_notes_enabled' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change back to default true
        Schema::table('bets', function (Blueprint $table) {
            $table->boolean('premium_notes_enabled')->default(true)->change();
        });
    }
};
