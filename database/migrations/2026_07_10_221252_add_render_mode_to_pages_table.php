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
        Schema::table('pages', function (Blueprint $table) {
            if (! Schema::hasColumn('pages', 'render_mode')) {
                $table->string('render_mode')->default('normal')->after('content');
            }

            if (! Schema::hasColumn('pages', 'raw_html')) {
                $table->longText('raw_html')->nullable()->after('render_mode');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['render_mode', 'raw_html']);
        });
    }
};
