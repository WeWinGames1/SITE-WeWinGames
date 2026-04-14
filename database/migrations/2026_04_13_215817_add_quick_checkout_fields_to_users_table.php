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
            $table->string('completion_token', 64)->nullable()->after('remember_token');
            $table->timestamp('completion_token_expires_at')->nullable()->after('completion_token');
            $table->string('registration_type', 20)->default('standard')->after('completion_token_expires_at');

            $table->index('completion_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['completion_token']);
            $table->dropColumn(['completion_token', 'completion_token_expires_at', 'registration_type']);
        });
    }
};
