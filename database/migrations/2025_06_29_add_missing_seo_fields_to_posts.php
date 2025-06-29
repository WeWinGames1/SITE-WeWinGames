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
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'seo_title')) {
                $table->string('seo_title')->nullable()->after('tags');
            }
            if (!Schema::hasColumn('posts', 'seo_description')) {
                $table->text('seo_description')->nullable()->after('seo_title');
            }
            if (!Schema::hasColumn('posts', 'seo_keywords')) {
                $table->text('seo_keywords')->nullable()->after('seo_description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'seo_title')) {
                $table->dropColumn('seo_title');
            }
            if (Schema::hasColumn('posts', 'seo_description')) {
                $table->dropColumn('seo_description');
            }
            if (Schema::hasColumn('posts', 'seo_keywords')) {
                $table->dropColumn('seo_keywords');
            }
        });
    }
};