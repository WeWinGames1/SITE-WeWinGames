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
        Schema::table('bets', function (Blueprint $table) {
            // MetaBet Prop Tile fields
            $table->string('metabet_prop_query_id')->nullable()->after('metabet_linked_at');
            $table->string('metabet_prop_name')->nullable()->after('metabet_prop_query_id');
            $table->string('metabet_prop_size')->nullable()->default('320x50')->after('metabet_prop_name');

            // MetaBet Parlay Tile fields
            $table->string('metabet_parlay_query_id')->nullable()->after('metabet_prop_size');
            $table->string('metabet_parlay_name')->nullable()->after('metabet_parlay_query_id');
            $table->string('metabet_parlay_size')->nullable()->default('350x350')->after('metabet_parlay_name');

            // Widget type preference (game, prop, parlay, or combination)
            $table->string('metabet_widget_type')->nullable()->default('game')->after('metabet_parlay_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropColumn([
                'metabet_prop_query_id',
                'metabet_prop_name',
                'metabet_prop_size',
                'metabet_parlay_query_id',
                'metabet_parlay_name',
                'metabet_parlay_size',
                'metabet_widget_type',
            ]);
        });
    }
};
