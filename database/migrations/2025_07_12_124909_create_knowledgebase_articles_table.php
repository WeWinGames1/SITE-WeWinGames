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
        Schema::create('knowledgebase_articles', function (Blueprint $table) {
            $table->id();
            $table->string('page_identifier')->index(); // e.g., 'admin.bets.index' or 'home'
            $table->string('title');
            $table->text('content'); // Rich text content
            $table->json('sections')->nullable(); // JSON array of sections with titles and content
            $table->string('screenshot_path')->nullable(); // Path to screenshot
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->enum('type', ['frontend', 'admin'])->default('frontend');
            $table->timestamps();

            $table->index(['page_identifier', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledgebase_articles');
    }
};
