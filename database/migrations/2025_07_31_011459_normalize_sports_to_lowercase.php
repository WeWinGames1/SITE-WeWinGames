<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all sports names in bets table to lowercase
        DB::statement('UPDATE bets SET sports = LOWER(sports) WHERE sports IS NOT NULL');
        DB::statement('UPDATE bets SET sport = LOWER(sport) WHERE sport IS NOT NULL');
        
        // Update all sports names in sports table to lowercase
        DB::statement('UPDATE sports SET name = LOWER(name) WHERE name IS NOT NULL');
        
        // Remove duplicate sports that may exist with different cases
        // First, get all sports grouped by lowercase name
        $sports = DB::table('sports')
            ->select(DB::raw('MIN(id) as keep_id, LOWER(name) as lower_name'))
            ->groupBy(DB::raw('LOWER(name)'))
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();
        
        foreach ($sports as $sport) {
            // Update all references to use the first sport ID
            $duplicates = DB::table('sports')
                ->whereRaw('LOWER(name) = ?', [$sport->lower_name])
                ->where('id', '!=', $sport->keep_id)
                ->pluck('id');
            
            foreach ($duplicates as $duplicateId) {
                // Update teams
                DB::table('teams')
                    ->where('sport_id', $duplicateId)
                    ->update(['sport_id' => $sport->keep_id]);
                
                // Update bets
                DB::table('bets')
                    ->where('sport_id', $duplicateId)
                    ->update(['sport_id' => $sport->keep_id]);
                
                // Update games if they have sport_id
                if (Schema::hasColumn('games', 'sport_id')) {
                    DB::table('games')
                        ->where('sport_id', $duplicateId)
                        ->update(['sport_id' => $sport->keep_id]);
                }
                
                // Delete the duplicate sport
                DB::table('sports')->where('id', $duplicateId)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible as we don't know the original case
        // We would need to store the original values before changing them
    }
};