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
            // Discord OAuth fields
            $table->string('discord_id')->nullable()->unique()->after('discord_username');
            $table->string('discord_discriminator')->nullable()->after('discord_id');
            $table->string('discord_avatar')->nullable()->after('discord_discriminator');
            $table->text('discord_access_token')->nullable()->after('discord_avatar');
            $table->text('discord_refresh_token')->nullable()->after('discord_access_token');
            $table->timestamp('discord_connected_at')->nullable()->after('discord_refresh_token');
            $table->timestamp('discord_token_expires_at')->nullable()->after('discord_connected_at');
            $table->json('discord_roles_synced')->nullable()->after('discord_token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'discord_id',
                'discord_discriminator',
                'discord_avatar',
                'discord_access_token',
                'discord_refresh_token',
                'discord_connected_at',
                'discord_token_expires_at',
                'discord_roles_synced',
            ]);
        });
    }
};
