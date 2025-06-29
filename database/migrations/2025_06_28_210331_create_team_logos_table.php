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
        Schema::create('team_logos', function (Blueprint $table) {
            $table->id();
            $table->string('sport', 50);
            $table->string('league', 50);
            $table->string('team_name');
            $table->string('logo_url', 500)->nullable();
            $table->string('api_id', 100)->nullable();
            $table->json('metadata')->nullable(); // For storing additional API data
            $table->timestamps();
            
            // Unique constraint to prevent duplicates
            $table->unique(['sport', 'league', 'team_name']);
            
            // Indexes for performance
            $table->index('sport');
            $table->index('league');
            $table->index('team_name');
            $table->index('api_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_logos');
    }
};
