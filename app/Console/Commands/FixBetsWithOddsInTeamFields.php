<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\Team;
use Illuminate\Console\Command;

class FixBetsWithOddsInTeamFields extends Command
{
    protected $signature = 'bets:fix-odds-in-teams {--dry-run : Run without making changes}';
    protected $description = 'Fix bets that have odds values in team_one or team_two fields';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Fixing bets with odds in team fields...');
        
        // Find bets with numeric values in team fields
        $betsToFix = Bet::all()->filter(function ($bet) {
            $teamOneIsOdds = $bet->team_one && preg_match('/^[+-]?\d+\.?\d*$/', $bet->team_one);
            $teamTwoIsOdds = $bet->team_two && preg_match('/^[+-]?\d+\.?\d*$/', $bet->team_two);
            return $teamOneIsOdds || $teamTwoIsOdds;
        });
        
        $this->info("Found {$betsToFix->count()} bets to fix");
        
        if ($betsToFix->isEmpty()) {
            $this->info('No bets need fixing!');
            return Command::SUCCESS;
        }
        
        $fixed = 0;
        $patterns = [
            'teamOneOdds' => 0,
            'teamTwoOdds' => 0,
            'bothOdds' => 0,
        ];
        
        foreach ($betsToFix as $bet) {
            $teamOneIsOdds = $bet->team_one && preg_match('/^[+-]?\d+\.?\d*$/', $bet->team_one);
            $teamTwoIsOdds = $bet->team_two && preg_match('/^[+-]?\d+\.?\d*$/', $bet->team_two);
            
            if ($teamOneIsOdds && $teamTwoIsOdds) {
                $patterns['bothOdds']++;
                // Both are odds - clear both
                if (!$dryRun) {
                    $bet->team_one = '';
                    $bet->team_two = '';
                    $bet->save();
                }
            } elseif ($teamOneIsOdds && !$teamTwoIsOdds) {
                $patterns['teamOneOdds']++;
                // team_one has odds, team_two has team name
                // In many cases, team_two might have "Team Name (Player)" format
                // Let's extract just the team name
                $teamName = $this->extractTeamName($bet->team_two);
                
                if (!$dryRun) {
                    $bet->team_one = ''; // Clear the odds (empty string for NOT NULL constraint)
                    $bet->team_two = $teamName; // Clean up team name
                    
                    // Try to link to existing team
                    if ($teamName) {
                        $team = Team::findByNameOrAlias($teamName);
                        if ($team) {
                            $bet->team_two_id = $team->id;
                        }
                    }
                    
                    $bet->save();
                }
            } elseif (!$teamOneIsOdds && $teamTwoIsOdds) {
                $patterns['teamTwoOdds']++;
                // team_one has team name, team_two has odds
                $teamName = $this->extractTeamName($bet->team_one);
                
                if (!$dryRun) {
                    $bet->team_one = $teamName; // Clean up team name
                    $bet->team_two = ''; // Clear the odds (empty string for NOT NULL constraint)
                    
                    // Try to link to existing team
                    if ($teamName) {
                        $team = Team::findByNameOrAlias($teamName);
                        if ($team) {
                            $bet->team_one_id = $team->id;
                        }
                    }
                    
                    $bet->save();
                }
            }
            
            $fixed++;
        }
        
        if ($dryRun) {
            $this->info('Dry run - no changes made');
        } else {
            $this->info("Fixed {$fixed} bets");
        }
        
        $this->info('Pattern breakdown:');
        $this->info("  - Odds in team_one only: {$patterns['teamOneOdds']}");
        $this->info("  - Odds in team_two only: {$patterns['teamTwoOdds']}");
        $this->info("  - Odds in both fields: {$patterns['bothOdds']}");
        
        // Show samples
        $this->info("\nSample bets that would be fixed:");
        foreach ($betsToFix->take(5) as $bet) {
            $this->line("  Bet #{$bet->id}:");
            $this->line("    team_one: '{$bet->team_one}'");
            $this->line("    team_two: '{$bet->team_two}'");
        }
        
        return Command::SUCCESS;
    }
    
    private function extractTeamName(?string $value): ?string
    {
        if (!$value) return null;
        
        // Remove player names in parentheses
        // "Milwaukee Brewers (F Peralta)" => "Milwaukee Brewers"
        $value = preg_replace('/\s*\([^)]+\)/', '', $value);
        
        // Remove "First 5 innings" and similar
        $value = preg_replace('/\s*(First \d+ innings?|F5|1st Half|2nd Half)/i', '', $value);
        
        // Trim and return
        return trim($value);
    }
}