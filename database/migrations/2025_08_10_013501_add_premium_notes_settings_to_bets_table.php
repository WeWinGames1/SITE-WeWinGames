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
            $table->boolean('premium_notes_enabled')->default(true)->after('premium_notes');
            $table->string('premium_notes_heading')->nullable()->after('premium_notes_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropColumn(['premium_notes_enabled', 'premium_notes_heading']);
        });
    }
};
