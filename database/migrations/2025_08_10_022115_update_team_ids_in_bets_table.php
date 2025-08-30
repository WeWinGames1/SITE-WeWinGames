<?php

use App\Models\Bet;
use App\Models\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update team IDs for existing bets based on team names
        $bets = Bet::whereNull('team_one_id')
            ->orWhereNull('team_two_id')
            ->get();

        foreach ($bets as $bet) {
            $updated = false;

            // Update team_one_id if it's null but team_one has a value
            if (! $bet->team_one_id && $bet->team_one) {
                $team = Team::where('name', $bet->team_one)->first();
                if ($team) {
                    $bet->team_one_id = $team->id;
                    $updated = true;
                }
            }

            // Update team_two_id if it's null but team_two has a value
            if (! $bet->team_two_id && $bet->team_two) {
                $team = Team::where('name', $bet->team_two)->first();
                if ($team) {
                    $bet->team_two_id = $team->id;
                    $updated = true;
                }
            }

            if ($updated) {
                $bet->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set team IDs back to null
        DB::table('bets')->update([
            'team_one_id' => null,
            'team_two_id' => null,
        ]);
    }
};
