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
        if (!Schema::hasTable('email_logs')) {
            Schema::create('email_logs', function (Blueprint $table) {
                $table->id();
                $table->string('to_email');
                $table->string('to_name')->nullable();
                $table->string('subject');
                $table->string('template_key')->nullable()->index();
                $table->enum('status', ['pending', 'sent', 'failed', 'delivered', 'opened', 'clicked', 'bounced'])
                    ->default('pending')->index();
                $table->string('message_id')->nullable()->index();
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('clicked_at')->nullable();
                $table->timestamp('bounced_at')->nullable();
                $table->timestamps();
                
                $table->index(['created_at', 'status']);
                $table->index('to_email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
