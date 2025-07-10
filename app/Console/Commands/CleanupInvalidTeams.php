<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Bet;
use Illuminate\Console\Command;

class CleanupInvalidTeams extends Command
{
    protected $signature = 'teams:cleanup {--dry-run : Run without making changes}';
    protected $description = 'Remove invalid teams that are just odds/numbers and update bet relationships';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Cleaning up invalid teams...');
        
        // Find teams that are just numbers/odds
        // We need to fetch all teams and filter in PHP for SQLite compatibility
        $invalidTeams = Team::all()->filter(function ($team) {
            // Check if the name is just a number with optional + or - and decimal
            return preg_match('/^[+-]?\d+\.?\d*$/', $team->name);
        });

        $this->info("Found {$invalidTeams->count()} invalid teams to remove");
        
        if ($invalidTeams->isEmpty()) {
            $this->info('No invalid teams found!');
            return Command::SUCCESS;
        }
        
        // Show sample of invalid teams
        $this->info('Sample invalid teams:');
        foreach ($invalidTeams->take(10) as $team) {
            $this->line("  - {$team->name}");
        }
        
        if (!$dryRun) {
            // First, set team_one_id and team_two_id to null for bets linked to these teams
            $affectedBets = 0;
            foreach ($invalidTeams as $team) {
                $count1 = Bet::where('team_one_id', $team->id)->update(['team_one_id' => null]);
                $count2 = Bet::where('team_two_id', $team->id)->update(['team_two_id' => null]);
                $affectedBets += $count1 + $count2;
            }
            
            $this->info("Updated {$affectedBets} bet relationships");
            
            // Delete the invalid teams
            $deletedCount = 0;
            foreach ($invalidTeams as $team) {
                $team->delete();
                $deletedCount++;
            }
            
            $this->info("Deleted {$deletedCount} invalid teams");
        } else {
            $this->info('Dry run - no changes made');
        }
        
        // Show stats of remaining valid teams
        $validTeams = Team::count();
        $this->info("Remaining valid teams: {$validTeams}");
        
        // Check for bets with numeric team names that need fixing
        $betsWithNumericTeams = Bet::where(function ($query) {
            $query->where('team_one', 'LIKE', '+%')
                ->orWhere('team_one', 'LIKE', '-%')
                ->orWhere('team_two', 'LIKE', '+%')
                ->orWhere('team_two', 'LIKE', '-%');
        })->count();
        
        if ($betsWithNumericTeams > 0) {
            $this->warn("Found {$betsWithNumericTeams} bets with numeric values in team_one/team_two columns");
            $this->warn("These bets likely have odds in the team columns instead of team names");
            
            // Show a sample
            $sampleBets = Bet::where(function ($query) {
                $query->where('team_one', 'LIKE', '+%')
                    ->orWhere('team_one', 'LIKE', '-%')
                    ->orWhere('team_two', 'LIKE', '+%')
                    ->orWhere('team_two', 'LIKE', '-%');
            })->limit(5)->get();
            
            $this->info('Sample bets with numeric team values:');
            foreach ($sampleBets as $bet) {
                $this->line("  Bet #{$bet->id}: team_one='{$bet->team_one}', team_two='{$bet->team_two}'");
            }
        }
        
        return Command::SUCCESS;
    }
}