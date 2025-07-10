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
        Schema::create('bet_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bet_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
            $table->string('team_name')->nullable(); // Store original name if team not found
            $table->integer('position')->default(1); // Order of team in parlay
            $table->enum('role', ['home', 'away', 'favorite', 'underdog', 'team_one', 'team_two', 'parlay'])->default('parlay');
            $table->string('spread')->nullable(); // For spread bets
            $table->string('line')->nullable(); // For over/under
            $table->timestamps();
            
            // Indexes
            $table->index(['bet_id', 'position']);
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bet_teams');
    }
};