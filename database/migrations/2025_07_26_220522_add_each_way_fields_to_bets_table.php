<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            // Add Each Way betting fields
            $table->boolean('is_each_way')->default(false)->after('wager_type');
            $table->decimal('each_way_stake', 10, 2)->nullable()->after('wager_amount');
            $table->decimal('place_payout', 10, 2)->nullable()->after('winning_amount');
        });

        // Update existing Each Way bets based on wager_type
        DB::table('bets')
            ->whereRaw('LOWER(wager_type) = ?', ['each way'])
            ->update([
                'is_each_way' => true,
                'each_way_stake' => DB::raw('wager_amount / 2'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropColumn(['is_each_way', 'each_way_stake', 'place_payout']);
        });
    }
};
