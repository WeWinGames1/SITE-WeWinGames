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
        Schema::table('notifications', function (Blueprint $table) {
            $table->json('target_tiers')->nullable()->after('data');
            $table->json('target_users')->nullable()->after('target_tiers');
            $table->enum('target_type', ['all', 'tiers', 'users'])->default('all')->after('target_users');
            $table->integer('sent_count')->default(0)->after('read_at');
            $table->integer('failed_count')->default(0)->after('sent_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['target_tiers', 'target_users', 'target_type', 'sent_count', 'failed_count']);
        });
    }
};
