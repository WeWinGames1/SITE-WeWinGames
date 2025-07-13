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
            if (!Schema::hasColumn('users', 'affiliate_id')) {
                $table->foreignId('affiliate_id')->nullable()->after('email')->constrained('affiliates')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'discord_username')) {
                $table->string('discord_username')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'affiliate_bound_at')) {
                $table->timestamp('affiliate_bound_at')->nullable()->after('affiliate_id');
            }
            if (!Schema::hasColumn('users', 'affiliate_bound_plan')) {
                $table->string('affiliate_bound_plan')->nullable()->after('affiliate_bound_at');
            }
            
            $table->index('affiliate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['affiliate_id']);
            $table->dropColumn(['affiliate_id', 'discord_username', 'affiliate_bound_at', 'affiliate_bound_plan']);
        });
    }
};
