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
        Schema::table('users', function (Blueprint $table) {
            $table->string('favorite_team')->nullable()->after('phone');
            $table->string('favorite_sport')->nullable()->after('favorite_team');
            $table->string('primary_betting_app')->nullable()->after('favorite_sport');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['favorite_team', 'favorite_sport', 'primary_betting_app']);
        });
    }
};
