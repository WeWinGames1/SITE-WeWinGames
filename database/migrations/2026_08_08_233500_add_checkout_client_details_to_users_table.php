<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The IP + user agent of the browser that actually paid. Sent to the X and
     * Reddit Conversion APIs as an extra match identifier — the Stripe webhook
     * request carries Stripe's own IP, so the buyer's has to be captured at
     * checkout time and stored. Kept separate from registration_ip, which can be
     * years old and from a different device.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'checkout_ip_address')) {
                $table->string('checkout_ip_address', 45)->nullable()->after('landing_url');
            }

            if (! Schema::hasColumn('users', 'checkout_user_agent')) {
                $table->text('checkout_user_agent')->nullable()->after('checkout_ip_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['checkout_ip_address', 'checkout_user_agent'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
