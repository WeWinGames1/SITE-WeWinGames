<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\Team;
use App\Models\BetTeam;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MigrateExistingParlays extends Command
{
    protected $signature = 'bets:migrate-parlays {--dry-run : Run without making changes}';
    protected $description = 'Identify and migrate existing parlay bets to the new structure';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Searching for potential parlay bets...');
        
        // Patterns that indicate parlays
        $parlayIndicators = [
            ' & ',
            ' and ',
            ' AND ',
            '+',
            ' / ',
            ' vs ',
            ' v ',
        ];
        
        // Find bets that might be parlays based on bet type
        $potentialParlays = Bet::where(function ($query) {
            $query->where('bet_type', 'LIKE', '%parlay%')
                  ->orWhere('bet_type', 'LIKE', '%accumulator%')
                  ->orWhere('bet_type', 'LIKE', '%multi%')
                  ->orWhere('wager_type', 'LIKE', '%parlay%')
                  ->orWhere('wager_type', 'LIKE', '%accumulator%')
                  ->orWhere('wager_type', 'LIKE', '%multi%')
                  ->orWhere('wager_name', 'LIKE', '%parlay%')
                  ->orWhere('wager_name', 'LIKE', '% & %')
                  ->orWhere('matches', 'LIKE', '% & %')
                  ->orWhere('matches', 'LIKE', '% @ %'); // For games like "Team1 @ Team2 & Team3 @ Team4"
        })
        ->get();
        
        $this->info("Found {$potentialParlays->count()} potential parlay bets");
        
        if ($potentialParlays->isEmpty()) {
            $this->info('No parlays found to migrate.');
            return Command::SUCCESS;
        }
        
        $migrated = 0;
        $failed = 0;
        
        foreach ($potentialParlays as $bet) {
            $teams = $this->extractTeamsFromParlay($bet);
            
            if (count($teams) < 2) {
                continue; // Not really a parlay
            }
            
            $this->line("\nBet #{$bet->id}:");
            $this->line("  Type: {$bet->bet_type} / {$bet->wager_type}");
            $this->line("  Teams found: " . count($teams));
            
            if (!$dryRun) {
                // Update bet as parlay
                $bet->update([
                    'is_parlay' => true,
                    'parlay_legs' => count($teams),
                ]);
                
                // Create bet_teams entries
                foreach ($teams as $index => $teamData) {
                    $team = null;
                    
                    // Try to find the team
                    if (!empty($teamData['name'])) {
                        $team = Team::findByNameOrAlias($teamData['name']);
                    }
                    
                    BetTeam::create([
                        'bet_id' => $bet->id,
                        'team_id' => $team ? $team->id : null,
                        'team_name' => $teamData['name'],
                        'position' => $index + 1,
                        'role' => 'parlay',
                    ]);
                    
                    $this->line("    - {$teamData['name']}" . ($team ? " (matched to: {$team->name})" : " (not matched)"));
                }
                
                $migrated++;
            } else {
                foreach ($teams as $teamData) {
                    $this->line("    - {$teamData['name']}");
                }
            }
        }
        
        $this->info("\nSummary:");
        $this->info("Parlays migrated: {$migrated}");
        $this->info("Failed: {$failed}");
        
        if ($dryRun) {
            $this->warn("Dry run mode - no changes were made");
        }
        
        return Command::SUCCESS;
    }
    
    private function extractTeamsFromParlay(Bet $bet): array
    {
        $teams = [];
        
        // Check all fields that might contain team information
        $fieldsToCheck = [
            'team_one',
            'team_two',
            'matches',
            'tips',
        ];
        
        foreach ($fieldsToCheck as $field) {
            if (empty($bet->$field)) continue;
            
            $value = $bet->$field;
            
            // Special handling for matches like "Team1 @ Team2 & Team3 @ Team4"
            if (Str::contains($value, ' & ') && Str::contains($value, ' @ ')) {
                // This is likely multiple games in a parlay
                $games = explode(' & ', $value);
                foreach ($games as $game) {
                    if (Str::contains($game, ' @ ')) {
                        $gameParts = explode(' @ ', $game);
                        foreach ($gameParts as $part) {
                            $teamName = $this->cleanTeamName($part);
                            if ($teamName && !$this->isOddsValue($teamName)) {
                                $teams[] = ['name' => $teamName];
                            }
                        }
                    }
                }
                
                if (count($teams) > 2) {
                    return $teams; // Found parlay teams
                }
            }
            
            // Check for simple "Team1 & Team2" format
            if (Str::contains($value, ' & ') && !Str::contains($value, ' @ ')) {
                $parts = explode(' & ', $value);
                foreach ($parts as $part) {
                    $teamName = $this->cleanTeamName($part);
                    if ($teamName && !$this->isOddsValue($teamName)) {
                        $teams[] = ['name' => $teamName];
                    }
                }
                
                if (count($teams) > 1) {
                    return $teams;
                }
            }
            
            // If no separator found but we have a team name, add it
            if (count($teams) == 0) {
                $teamName = $this->cleanTeamName($value);
                if ($teamName && !$this->isOddsValue($teamName)) {
                    $teams[] = ['name' => $teamName];
                }
            }
        }
        
        return array_unique($teams, SORT_REGULAR);
    }
    
    private function cleanTeamName(string $name): ?string
    {
        // Remove number prefixes like "1. "
        $name = preg_replace('/^\d+\.\s+/', '', $name);
        
        // Remove common bet descriptors
        $name = preg_replace('/\s*\(.*?\)/', '', $name); // Remove parentheses content
        $name = preg_replace('/\s*(First \d+ innings?|F5|1st Half|2nd Half)/i', '', $name);
        $name = preg_replace('/\s*(to win|winner|spread|over|under)/i', '', $name);
        $name = preg_replace('/\s*[@]\s*/', ' @ ', $name); // Normalize @ symbol
        
        $name = trim($name);
        
        // Skip if empty or too short
        if (strlen($name) < 3) {
            return null;
        }
        
        return $name;
    }
    
    private function isOddsValue(string $value): bool
    {
        // Check if this is just an odds value
        return preg_match('/^[+-]?\d+\.?\d*$/', $value);
    }
}