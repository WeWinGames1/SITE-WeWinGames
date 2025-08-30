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
        Schema::create('push_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->string('recipients_type'); // all, push_enabled, tier
            $table->string('tier')->nullable();
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->foreignId('sent_by')->constrained('users');
            $table->json('metadata')->nullable(); // For storing additional data
            $table->timestamps();

            $table->index('created_at');
            $table->index('sent_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification_logs');
    }
};
