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
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description')->nullable();
            
            // Discount type and amount
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_amount', 10, 2);
            
            // Application rules
            $table->enum('apply_to', ['first_payment', 'forever', 'specific_months']);
            $table->integer('months_count')->nullable(); // For specific_months option
            
            // Usage limits
            $table->integer('max_uses')->nullable(); // Total uses allowed
            $table->integer('max_uses_per_customer')->default(1);
            $table->integer('times_used')->default(0);
            
            // Validity period
            $table->datetime('valid_from')->nullable();
            $table->datetime('valid_until')->nullable();
            
            // Restrictions
            $table->json('applicable_products')->nullable(); // Array of stripe_product_ids
            $table->decimal('minimum_amount', 10, 2)->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->string('stripe_coupon_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index(['valid_from', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_codes');
    }
};
