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
        // Add indexes to bets table
        Schema::table('bets', function (Blueprint $table) {
            $table->index('user_id', 'idx_bets_user_id');
            $table->index('sport_id', 'idx_bets_sport_id');
            $table->index('game_id', 'idx_bets_game_id');
            $table->index('operator_id', 'idx_bets_operator_id');
            $table->index('status', 'idx_bets_status');
            $table->index('created_at', 'idx_bets_created_at');
            $table->index('placed_at', 'idx_bets_placed_at');
            $table->index(['user_id', 'status'], 'idx_bets_user_status');
            $table->index(['sport_id', 'status'], 'idx_bets_sport_status');
            $table->index(['status', 'created_at'], 'idx_bets_status_created');
        });

        // Add indexes to games table
        Schema::table('games', function (Blueprint $table) {
            $table->index('sport_id', 'idx_games_sport_id');
            $table->index('home_team_id', 'idx_games_home_team_id');
            $table->index('away_team_id', 'idx_games_away_team_id');
            $table->index('game_date', 'idx_games_date');
            $table->index('status', 'idx_games_status');
            $table->index(['sport_id', 'game_date'], 'idx_games_sport_date');
        });

        // Add indexes to teams table
        Schema::table('teams', function (Blueprint $table) {
            $table->index('sport_id', 'idx_teams_sport_id');
            $table->index('slug', 'idx_teams_slug');
        });

        // Add indexes to users table for better query performance
        Schema::table('users', function (Blueprint $table) {
            $table->index('email', 'idx_users_email');
            $table->index('created_at', 'idx_users_created_at');
        });

        // Add indexes to subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index('user_id', 'idx_subscriptions_user_id');
            $table->index('stripe_status', 'idx_subscriptions_status');
            $table->index('ends_at', 'idx_subscriptions_ends_at');
            $table->index(['user_id', 'stripe_status'], 'idx_subscriptions_user_status');
        });

        // Add indexes to pages table
        Schema::table('pages', function (Blueprint $table) {
            $table->index('slug', 'idx_pages_slug');
            $table->index('status', 'idx_pages_status');
            $table->index(['status', 'created_at'], 'idx_pages_status_created');
        });

        // Add indexes to posts table
        Schema::table('posts', function (Blueprint $table) {
            $table->index('slug', 'idx_posts_slug');
            $table->index('published_at', 'idx_posts_published_at');
            $table->index('is_published', 'idx_posts_is_published');
            $table->index(['is_published', 'published_at'], 'idx_posts_published_date');
        });

        // Add indexes to model_has_roles table for Spatie permissions
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->index(['model_type', 'model_id'], 'idx_model_has_roles_model');
        });

        // Add indexes to personal_access_tokens for API authentication
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->index('tokenable_type', 'idx_pat_tokenable_type');
            $table->index('tokenable_id', 'idx_pat_tokenable_id');
            $table->index(['tokenable_type', 'tokenable_id'], 'idx_pat_tokenable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from bets table
        Schema::table('bets', function (Blueprint $table) {
            $table->dropIndex('idx_bets_user_id');
            $table->dropIndex('idx_bets_sport_id');
            $table->dropIndex('idx_bets_game_id');
            $table->dropIndex('idx_bets_operator_id');
            $table->dropIndex('idx_bets_status');
            $table->dropIndex('idx_bets_created_at');
            $table->dropIndex('idx_bets_placed_at');
            $table->dropIndex('idx_bets_user_status');
            $table->dropIndex('idx_bets_sport_status');
            $table->dropIndex('idx_bets_status_created');
        });

        // Remove indexes from games table
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex('idx_games_sport_id');
            $table->dropIndex('idx_games_home_team_id');
            $table->dropIndex('idx_games_away_team_id');
            $table->dropIndex('idx_games_date');
            $table->dropIndex('idx_games_status');
            $table->dropIndex('idx_games_sport_date');
        });

        // Remove indexes from teams table
        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex('idx_teams_sport_id');
            $table->dropIndex('idx_teams_slug');
        });

        // Remove indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_created_at');
        });

        // Remove indexes from subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_subscriptions_user_id');
            $table->dropIndex('idx_subscriptions_status');
            $table->dropIndex('idx_subscriptions_ends_at');
            $table->dropIndex('idx_subscriptions_user_status');
        });

        // Remove indexes from pages table
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex('idx_pages_slug');
            $table->dropIndex('idx_pages_status');
            $table->dropIndex('idx_pages_status_created');
        });

        // Remove indexes from posts table
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('idx_posts_slug');
            $table->dropIndex('idx_posts_published_at');
            $table->dropIndex('idx_posts_is_published');
            $table->dropIndex('idx_posts_published_date');
        });

        // Remove indexes from model_has_roles table
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropIndex('idx_model_has_roles_model');
        });

        // Remove indexes from personal_access_tokens
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex('idx_pat_tokenable_type');
            $table->dropIndex('idx_pat_tokenable_id');
            $table->dropIndex('idx_pat_tokenable');
        });
    }
};
