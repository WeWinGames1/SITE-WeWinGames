<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Helper function to check if index exists
        $indexExists = function($table, $columns) {
            $columns = is_array($columns) ? $columns : [$columns];
            $indexName = $table . '_' . implode('_', $columns) . '_index';
            
            // For SQLite
            if (DB::getDriverName() === 'sqlite') {
                $indexes = DB::select("PRAGMA index_list('$table')");
                foreach ($indexes as $index) {
                    if ($index->name === $indexName) {
                        return true;
                    }
                }
                return false;
            }
            
            // For MySQL
            $exists = DB::select("SHOW INDEX FROM $table WHERE Key_name = ?", [$indexName]);
            return !empty($exists);
        };

        // Add indexes to bets table for better query performance
        Schema::table('bets', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('bets', 'status')) {
                $table->index('status');
            }
            if (!$indexExists('bets', 'sport_id')) {
                $table->index('sport_id');
            }
            if (!$indexExists('bets', 'user_id')) {
                $table->index('user_id');
            }
            if (!$indexExists('bets', 'operator_id')) {
                $table->index('operator_id');
            }
            if (!$indexExists('bets', 'game_at')) {
                $table->index('game_at');
            }
            if (!$indexExists('bets', 'betting_date')) {
                $table->index('betting_date');
            }
            if (!$indexExists('bets', ['status', 'game_at'])) {
                $table->index(['status', 'game_at']);
            }
            if (!$indexExists('bets', ['sport_id', 'status'])) {
                $table->index(['sport_id', 'status']);
            }
        });

        // Add indexes to subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('subscriptions', 'stripe_status')) {
                $table->index('stripe_status');
            }
            if (!$indexExists('subscriptions', ['user_id', 'stripe_status'])) {
                $table->index(['user_id', 'stripe_status']);
            }
            if (!$indexExists('subscriptions', 'trial_ends_at')) {
                $table->index('trial_ends_at');
            }
        });

        // Add indexes to users table
        Schema::table('users', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('users', 'created_at')) {
                $table->index('created_at');
            }
            if (!$indexExists('users', ['created_at', 'email'])) {
                $table->index(['created_at', 'email']);
            }
        });

        // Add indexes to testimonials table
        if (Schema::hasTable('testimonials')) {
            Schema::table('testimonials', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('testimonials', 'published')) {
                    $table->index('published');
                }
                if (!$indexExists('testimonials', 'sort_order')) {
                    $table->index('sort_order');
                }
                if (!$indexExists('testimonials', ['published', 'sort_order'])) {
                    $table->index(['published', 'sort_order']);
                }
            });
        }

        // Add indexes to pages table
        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('pages', 'slug')) {
                    $table->index('slug');
                }
                if (!$indexExists('pages', 'published')) {
                    $table->index('published');
                }
            });
        }

        // Add indexes to posts table
        if (Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('posts', 'slug')) {
                    $table->index('slug');
                }
                if (!$indexExists('posts', 'status')) {
                    $table->index('status');
                }
                if (!$indexExists('posts', 'published_at')) {
                    $table->index('published_at');
                }
                if (!$indexExists('posts', ['status', 'published_at'])) {
                    $table->index(['status', 'published_at']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Helper function to check if index exists before dropping
        $dropIndexIfExists = function($table, $columns) {
            $columns = is_array($columns) ? $columns : [$columns];
            $indexName = $table . '_' . implode('_', $columns) . '_index';
            
            // For SQLite
            if (DB::getDriverName() === 'sqlite') {
                $indexes = DB::select("PRAGMA index_list('$table')");
                foreach ($indexes as $index) {
                    if ($index->name === $indexName) {
                        DB::statement("DROP INDEX $indexName");
                        return;
                    }
                }
                return;
            }
            
            // For MySQL
            $exists = DB::select("SHOW INDEX FROM $table WHERE Key_name = ?", [$indexName]);
            if (!empty($exists)) {
                DB::statement("DROP INDEX $indexName ON $table");
            }
        };

        // Drop indexes from bets table
        $dropIndexIfExists('bets', 'status');
        $dropIndexIfExists('bets', 'sport_id');
        $dropIndexIfExists('bets', 'user_id');
        $dropIndexIfExists('bets', 'operator_id');
        $dropIndexIfExists('bets', 'game_at');
        $dropIndexIfExists('bets', 'betting_date');
        $dropIndexIfExists('bets', ['status', 'game_at']);
        $dropIndexIfExists('bets', ['sport_id', 'status']);

        // Drop indexes from subscriptions table
        $dropIndexIfExists('subscriptions', 'stripe_status');
        $dropIndexIfExists('subscriptions', ['user_id', 'stripe_status']);
        $dropIndexIfExists('subscriptions', 'trial_ends_at');

        // Drop indexes from users table
        $dropIndexIfExists('users', 'created_at');
        $dropIndexIfExists('users', ['created_at', 'email']);

        // Drop indexes from testimonials table
        if (Schema::hasTable('testimonials')) {
            $dropIndexIfExists('testimonials', 'published');
            $dropIndexIfExists('testimonials', 'sort_order');
            $dropIndexIfExists('testimonials', ['published', 'sort_order']);
        }

        // Drop indexes from pages table
        if (Schema::hasTable('pages')) {
            $dropIndexIfExists('pages', 'slug');
            $dropIndexIfExists('pages', 'published');
        }

        // Drop indexes from posts table
        if (Schema::hasTable('posts')) {
            $dropIndexIfExists('posts', 'slug');
            $dropIndexIfExists('posts', 'status');
            $dropIndexIfExists('posts', 'published_at');
            $dropIndexIfExists('posts', ['status', 'published_at']);
        }
    }
};