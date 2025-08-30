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
        Schema::table('stripe_products', function (Blueprint $table) {
            if (! Schema::hasColumn('stripe_products', 'is_current')) {
                $table->boolean('is_current')->default(true)->after('is_active');
            }
            if (! Schema::hasColumn('stripe_products', 'version')) {
                $table->string('version')->nullable()->after('is_current');
            }
            if (! Schema::hasColumn('stripe_products', 'legacy_price')) {
                $table->decimal('legacy_price', 8, 2)->nullable()->after('version');
            }
            if (! Schema::hasColumn('stripe_products', 'superseded_at')) {
                $table->timestamp('superseded_at')->nullable()->after('legacy_price');
            }
            if (! Schema::hasColumn('stripe_products', 'superseded_by_product_id')) {
                $table->string('superseded_by_product_id')->nullable()->after('superseded_at');
            }

            // Add index for faster lookups with short name
            $table->index(['tier', 'billing_period', 'is_current'], 'tier_period_current_idx');
            $table->index('superseded_by_product_id');
        });

        // Create a table to track price migrations
        Schema::create('stripe_price_migrations', function (Blueprint $table) {
            $table->id();
            $table->string('old_stripe_price_id');
            $table->string('new_stripe_price_id');
            $table->string('tier');
            $table->string('billing_period');
            $table->decimal('old_price', 8, 2);
            $table->decimal('new_price', 8, 2);
            $table->text('notes')->nullable();
            $table->timestamp('effective_date');
            $table->timestamps();

            // Create separate indexes to avoid key length issues
            $table->index('old_stripe_price_id', 'old_price_idx');
            $table->index('new_stripe_price_id', 'new_price_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stripe_products', function (Blueprint $table) {
            $table->dropIndex(['tier', 'billing_period', 'is_current']);
            $table->dropIndex(['superseded_by_product_id']);

            $table->dropColumn([
                'is_current',
                'version',
                'legacy_price',
                'superseded_at',
                'superseded_by_product_id',
            ]);
        });

        Schema::dropIfExists('stripe_price_migrations');
    }
};
