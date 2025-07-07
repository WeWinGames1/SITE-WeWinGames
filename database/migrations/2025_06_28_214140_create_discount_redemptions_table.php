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
        Schema::create('discount_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_code_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('subscription_id')->nullable();
            $table->decimal('discount_applied', 10, 2);
            $table->string('stripe_invoice_id')->nullable();
            $table->timestamps();

            // Prevent duplicate redemptions
            $table->unique(['discount_code_id', 'user_id', 'subscription_id'], 'disc_redemptions_unique');

            // Indexes
            $table->index('user_id');
            $table->index('subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_redemptions');
    }
};
