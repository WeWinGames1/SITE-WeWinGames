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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'twclid')) {
                $table->string('twclid')->nullable()->after('registration_type');
            }
            if (! Schema::hasColumn('users', 'utm_source')) {
                $table->string('utm_source')->nullable()->after('twclid');
            }
            if (! Schema::hasColumn('users', 'utm_medium')) {
                $table->string('utm_medium')->nullable()->after('utm_source');
            }
            if (! Schema::hasColumn('users', 'utm_campaign')) {
                $table->string('utm_campaign')->nullable()->after('utm_medium');
            }
            if (! Schema::hasColumn('users', 'utm_content')) {
                $table->string('utm_content')->nullable()->after('utm_campaign');
            }
            if (! Schema::hasColumn('users', 'landing_url')) {
                $table->text('landing_url')->nullable()->after('utm_content');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'twclid',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_content',
                'landing_url',
            ]);
        });
    }
};
