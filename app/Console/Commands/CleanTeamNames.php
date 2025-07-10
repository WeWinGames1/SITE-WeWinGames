<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Bet;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CleanTeamNames extends Command
{
    protected $signature = 'teams:clean-names {--dry-run : Run without making changes}';
    protected $description = 'Clean team names by removing number prefixes like "1. "';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Cleaning team names...');
        
        // Find teams with number prefixes
        $teamsToClean = Team::where('name', 'LIKE', '1. %')
            ->orWhere('name', 'LIKE', '2. %')
            ->orWhere('name', 'LIKE', '3. %')
            ->orWhere('name', 'LIKE', '4. %')
            ->orWhere('name', 'LIKE', '5. %')
            ->orWhere('name', 'LIKE', '6. %')
            ->orWhere('name', 'LIKE', '7. %')
            ->orWhere('name', 'LIKE', '8. %')
            ->orWhere('name', 'LIKE', '9. %')
            ->get();
        
        $this->info("Found {$teamsToClean->count()} teams with number prefixes");
        
        if ($teamsToClean->isEmpty()) {
            $this->info('No teams need cleaning!');
            return Command::SUCCESS;
        }
        
        $cleaned = 0;
        $duplicates = 0;
        
        foreach ($teamsToClean as $team) {
            $originalName = $team->name;
            // Remove number prefix (e.g., "1. FC Heidenheim" -> "FC Heidenheim")
            $cleanName = preg_replace('/^\d+\.\s+/', '', $originalName);
            
            $this->line("Team: '{$originalName}' -> '{$cleanName}'");
            
            // Check if a team with the clean name already exists
            $existingTeam = Team::where('name', $cleanName)
                ->where('id', '!=', $team->id)
                ->where('sport_id', $team->sport_id)
                ->first();
            
            if ($existingTeam) {
                $this->warn("  Duplicate found! Team '{$cleanName}' already exists (ID: {$existingTeam->id})");
                
                if (!$dryRun) {
                    // Update bets to point to the existing team
                    $updated1 = Bet::where('team_one_id', $team->id)->update(['team_one_id' => $existingTeam->id]);
                    $updated2 = Bet::where('team_two_id', $team->id)->update(['team_two_id' => $existingTeam->id]);
                    
                    $this->info("  Updated " . ($updated1 + $updated2) . " bet references");
                    
                    // Delete the duplicate team
                    $team->delete();
                    $this->info("  Deleted duplicate team");
                }
                
                $duplicates++;
            } else {
                if (!$dryRun) {
                    $team->name = $cleanName;
                    // Generate unique slug
                    $baseSlug = Str::slug($cleanName);
                    $slug = $baseSlug;
                    $counter = 1;
                    
                    while (Team::where('slug', $slug)->where('id', '!=', $team->id)->exists()) {
                        $slug = $baseSlug . '-' . $counter;
                        $counter++;
                    }
                    
                    $team->slug = $slug;
                    $team->save();
                    $this->info("  Updated team name");
                }
                $cleaned++;
            }
        }
        
        $this->info("\nSummary:");
        $this->info("Teams cleaned: {$cleaned}");
        $this->info("Duplicates merged: {$duplicates}");
        
        if ($dryRun) {
            $this->warn("Dry run mode - no changes were made");
        }
        
        // Also clean bet columns
        $this->cleanBetTeamNames($dryRun);
        
        return Command::SUCCESS;
    }
    
    private function cleanBetTeamNames(bool $dryRun)
    {
        $this->info("\nCleaning team names in bet columns...");
        
        // Find bets with number prefixes in team names
        $betsToClean = Bet::where(function ($query) {
            $query->where('team_one', 'LIKE', '1. %')
                ->orWhere('team_one', 'LIKE', '2. %')
                ->orWhere('team_one', 'LIKE', '3. %')
                ->orWhere('team_one', 'LIKE', '4. %')
                ->orWhere('team_one', 'LIKE', '5. %')
                ->orWhere('team_two', 'LIKE', '1. %')
                ->orWhere('team_two', 'LIKE', '2. %')
                ->orWhere('team_two', 'LIKE', '3. %')
                ->orWhere('team_two', 'LIKE', '4. %')
                ->orWhere('team_two', 'LIKE', '5. %');
        })->get();
        
        $this->info("Found {$betsToClean->count()} bets with number prefixes in team names");
        
        $cleaned = 0;
        
        foreach ($betsToClean as $bet) {
            $changed = false;
            
            if (preg_match('/^\d+\.\s+/', $bet->team_one)) {
                $cleanTeamOne = preg_replace('/^\d+\.\s+/', '', $bet->team_one);
                if (!$dryRun) {
                    $bet->team_one = $cleanTeamOne;
                    
                    // Try to link to team
                    $team = Team::findByNameOrAlias($cleanTeamOne);
                    if ($team && !$bet->team_one_id) {
                        $bet->team_one_id = $team->id;
                    }
                }
                $changed = true;
            }
            
            if (preg_match('/^\d+\.\s+/', $bet->team_two)) {
                $cleanTeamTwo = preg_replace('/^\d+\.\s+/', '', $bet->team_two);
                if (!$dryRun) {
                    $bet->team_two = $cleanTeamTwo;
                    
                    // Try to link to team
                    $team = Team::findByNameOrAlias($cleanTeamTwo);
                    if ($team && !$bet->team_two_id) {
                        $bet->team_two_id = $team->id;
                    }
                }
                $changed = true;
            }
            
            if ($changed) {
                if (!$dryRun) {
                    $bet->save();
                }
                $cleaned++;
            }
        }
        
        $this->info("Cleaned {$cleaned} bet records");
    }
}