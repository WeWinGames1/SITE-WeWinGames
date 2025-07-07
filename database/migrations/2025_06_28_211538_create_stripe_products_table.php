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
        Schema::create('stripe_products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Display name (e.g., "Gold Monthly")
            $table->enum('tier', ['Bronze', 'Silver', 'Gold', 'Platinum']);
            $table->enum('billing_period', ['daily', 'weekly', 'monthly']);
            $table->decimal('price', 10, 2); // Price in dollars
            $table->string('stripe_product_id')->nullable(); // Stripe Product ID (prod_xxx)
            $table->string('stripe_price_id')->nullable(); // Stripe Price ID (price_xxx)
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable(); // Array of features for this tier
            $table->integer('sort_order')->default(0);
            $table->string('badge_text')->nullable(); // e.g., "Best Value"
            $table->timestamps();

            // Indexes
            $table->unique(['tier', 'billing_period']);
            $table->index('stripe_product_id');
            $table->index('stripe_price_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_products');
    }
};
