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
        // Track registration attempts
        Schema::create('registration_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('email')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('fingerprint', 64);
            $table->boolean('successful')->default(false);
            $table->json('checks_performed')->nullable();
            $table->timestamps();

            $table->index('ip_address');
            $table->index('email');
            $table->index('fingerprint');
            $table->index('created_at');
        });

        // Spam email domains blacklist
        Schema::create('spam_email_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        // IP blacklist
        Schema::create('ip_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_attempts');
        Schema::dropIfExists('spam_email_domains');
        Schema::dropIfExists('ip_blacklist');
    }
};
