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
        Schema::create('coupon_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('coupon_code', 50);
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->string('subscription_id')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_coupon_id')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('coupon_code');
            $table->index('subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_usage');
    }
};
