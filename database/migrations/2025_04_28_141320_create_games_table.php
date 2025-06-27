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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->datetime('game_date');
            $table->unsignedBigInteger('sport_id');
            $table->unsignedBigInteger('operator_id');
            $table->foreign('sport_id')->references('id')->on('sports')->onDelete('cascade');
            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade');
            $table->string('game_name'); // e.g., scheduled, ongoing, completed  
            $table->string('props');
            $table->string('line');
            $table->string('wager_team')->nullable();
            $table->string('post_availablity')->default('Silver');
            $table->string('odds');
            $table->string('type');
            $table->string('subsection')->nullable();
            $table->string('team1');
            $table->string('team2');
            $table->string('team1_img')->nullable();
            $table->string('team2_img')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
