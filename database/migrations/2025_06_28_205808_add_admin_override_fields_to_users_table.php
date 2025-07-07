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
            $table->boolean('is_ambassador')->default(false)->after('remember_token');
            $table->boolean('is_gifted')->default(false)->after('is_ambassador');
            $table->boolean('admin_override')->default(false)->after('is_gifted');
            $table->date('override_expiry')->nullable()->after('admin_override');
            $table->string('override_tier', 50)->nullable()->after('override_expiry');

            // Add indexes for performance
            $table->index('is_ambassador');
            $table->index('is_gifted');
            $table->index('admin_override');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_ambassador']);
            $table->dropIndex(['is_gifted']);
            $table->dropIndex(['admin_override']);

            $table->dropColumn([
                'is_ambassador',
                'is_gifted',
                'admin_override',
                'override_expiry',
                'override_tier',
            ]);
        });
    }
};
