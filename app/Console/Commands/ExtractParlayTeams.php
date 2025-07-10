<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Sport;
use App\Models\League;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ExtractParlayTeams extends Command
{
    protected $signature = 'teams:extract-parlays {--dry-run : Run without making changes}';
    protected $description = 'Extract individual teams from parlay team names';

    private $createdTeams = [];

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Extracting teams from parlay names...');
        
        // Find teams that look like parlays
        $parlayTeams = Team::where('name', 'LIKE', '%&%')
            ->orWhere('name', 'LIKE', '%@%')
            ->get();
        
        $this->info("Found {$parlayTeams->count()} potential parlay team entries");
        
        if ($parlayTeams->isEmpty()) {
            $this->info('No parlay teams found!');
            return Command::SUCCESS;
        }
        
        $processed = 0;
        $teamsCreated = 0;
        
        foreach ($parlayTeams as $parlayTeam) {
            $this->line("\nProcessing: {$parlayTeam->name}");
            
            $teams = $this->extractTeamsFromParlayName($parlayTeam->name);
            
            if (count($teams) < 2) {
                $this->warn("  Could not extract multiple teams, skipping...");
                continue;
            }
            
            $this->info("  Extracted " . count($teams) . " teams:");
            
            foreach ($teams as $teamName) {
                $this->line("    - {$teamName}");
                
                if (!$dryRun) {
                    // Check if team already exists
                    $existingTeam = Team::findByNameOrAlias($teamName, $parlayTeam->sport_id, $parlayTeam->league_id);
                    
                    if (!$existingTeam) {
                        // Create the team
                        $slug = $this->generateUniqueSlug($teamName);
                        
                        $newTeam = Team::create([
                            'name' => $teamName,
                            'slug' => $slug,
                            'sport_id' => $parlayTeam->sport_id,
                            'league_id' => $parlayTeam->league_id,
                            'is_active' => true,
                        ]);
                        
                        $this->info("      Created new team: {$teamName}");
                        $teamsCreated++;
                        $this->createdTeams[] = $newTeam;
                    } else {
                        $this->line("      Team already exists: {$existingTeam->name}");
                    }
                }
            }
            
            // Optionally delete the parlay team entry
            if (!$dryRun && count($teams) >= 2) {
                // Check if this "team" has any associated bets
                $betCount = $parlayTeam->betsAsTeamOne()->count() + $parlayTeam->betsAsTeamTwo()->count();
                
                if ($betCount == 0) {
                    $parlayTeam->delete();
                    $this->info("  Deleted parlay team entry (no associated bets)");
                } else {
                    $this->warn("  Kept parlay team entry (has {$betCount} associated bets)");
                }
            }
            
            $processed++;
        }
        
        $this->info("\nSummary:");
        $this->info("Parlay entries processed: {$processed}");
        $this->info("Individual teams created: {$teamsCreated}");
        
        if ($dryRun) {
            $this->warn("Dry run mode - no changes were made");
        }
        
        return Command::SUCCESS;
    }
    
    private function extractTeamsFromParlayName(string $name): array
    {
        $teams = [];
        
        // Remove number prefixes
        $name = preg_replace('/^\d+\.\s+/', '', $name);
        
        // List of known team names that contain "&" 
        $teamsWithAmpersand = [
            'Brighton & Hove Albion',
            'Hamilton Academical FC',
            'Queen\'s Park Rangers',
            'Dagenham & Redbridge',
            'Rushden & Diamonds',
            'Boston United',
            'Dag & Red',
            'Texas A&M',
            'Texas A&M-Corpus Christi',
            'Texas A&M-Commerce',
            'William & Mary',
            'Washington & Lee',
        ];
        
        // Protect known team names by replacing & temporarily
        $protectedName = $name;
        foreach ($teamsWithAmpersand as $teamName) {
            if (Str::contains($protectedName, $teamName)) {
                $placeholder = str_replace('&', '[[AMPERSAND]]', $teamName);
                $protectedName = str_replace($teamName, $placeholder, $protectedName);
            }
        }
        
        // Handle "Team1 @ Team2 & Team3 @ Team4" format
        if (Str::contains($protectedName, ' & ') && Str::contains($protectedName, ' @ ')) {
            $games = explode(' & ', $protectedName);
            foreach ($games as $game) {
                if (Str::contains($game, ' @ ')) {
                    $gameParts = explode(' @ ', $game);
                    foreach ($gameParts as $part) {
                        $teamName = $this->cleanTeamName(str_replace('[[AMPERSAND]]', '&', $part));
                        if ($teamName) {
                            $teams[] = $teamName;
                        }
                    }
                } else {
                    // Single team in this part
                    $teamName = $this->cleanTeamName(str_replace('[[AMPERSAND]]', '&', $game));
                    if ($teamName) {
                        $teams[] = $teamName;
                    }
                }
            }
        }
        // Handle "Team1 & Team2" format
        elseif (Str::contains($protectedName, ' & ')) {
            $parts = explode(' & ', $protectedName);
            foreach ($parts as $part) {
                $teamName = $this->cleanTeamName(str_replace('[[AMPERSAND]]', '&', $part));
                if ($teamName) {
                    $teams[] = $teamName;
                }
            }
        }
        // Handle "Team1 @ Team2" format (single game, not parlay)
        elseif (Str::contains($protectedName, ' @ ')) {
            $parts = explode(' @ ', $protectedName);
            foreach ($parts as $part) {
                $teamName = $this->cleanTeamName(str_replace('[[AMPERSAND]]', '&', $part));
                if ($teamName) {
                    $teams[] = $teamName;
                }
            }
        }
        
        return array_unique($teams);
    }
    
    private function cleanTeamName(string $name): ?string
    {
        // Remove number prefixes
        $name = preg_replace('/^\d+\.\s+/', '', $name);
        
        // Remove common suffixes
        $name = preg_replace('/\s*\(.*?\)/', '', $name); // Remove parentheses
        $name = preg_replace('/\s*(to win|winner|spread|over|under)/i', '', $name);
        
        $name = trim($name);
        
        // Skip if too short or just numbers
        if (strlen($name) < 3 || preg_match('/^[+-]?\d+\.?\d*$/', $name)) {
            return null;
        }
        
        return $name;
    }
    
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;
        
        while (Team::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}