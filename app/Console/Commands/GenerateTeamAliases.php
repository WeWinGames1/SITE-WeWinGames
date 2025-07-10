<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\TeamAlias;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateTeamAliases extends Command
{
    protected $signature = 'teams:generate-aliases {--dry-run : Run without creating aliases}';
    protected $description = 'Generate common team aliases based on patterns';

    private $patterns = [
        // Abbreviations
        'FC' => ['Football Club', 'F.C.'],
        'CF' => ['Club de Fútbol', 'C.F.'],
        'AFC' => ['Association Football Club', 'A.F.C.'],
        'SC' => ['Soccer Club', 'Sporting Club', 'S.C.'],
        'AC' => ['Associazione Calcio', 'A.C.'],
        'AS' => ['Associazione Sportiva', 'A.S.'],
        'RC' => ['Racing Club', 'R.C.'],
        'CD' => ['Club Deportivo', 'C.D.'],
        
        // Common variations
        'United' => ['Utd', 'Utd.'],
        'City' => ['C.'],
    ];

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Generating team aliases...');
        
        $teams = Team::with('aliases')->get();
        $created = 0;
        $skipped = 0;
        
        foreach ($teams as $team) {
            $existingAliases = $team->aliases->pluck('alias')->toArray();
            $existingAliases[] = $team->name; // Include the team name itself
            
            $potentialAliases = $this->generateAliases($team->name);
            
            foreach ($potentialAliases as $alias) {
                // Skip if alias already exists
                if (in_array($alias, $existingAliases)) {
                    $skipped++;
                    continue;
                }
                
                // Skip if alias is too similar to team name
                if ($this->isTooSimilar($team->name, $alias)) {
                    $skipped++;
                    continue;
                }
                
                // Check if another team already uses this alias
                $existingAlias = TeamAlias::where('alias', $alias)->first();
                if ($existingAlias) {
                    $this->warn("Alias '{$alias}' already used by team: " . $existingAlias->team->name);
                    $skipped++;
                    continue;
                }
                
                if (!$dryRun) {
                    TeamAlias::create([
                        'team_id' => $team->id,
                        'alias' => $alias,
                    ]);
                }
                
                $this->line("Created alias '{$alias}' for team: {$team->name}");
                $created++;
            }
        }
        
        $this->info("\nSummary:");
        $this->info("Aliases created: {$created}");
        $this->info("Aliases skipped: {$skipped}");
        
        if ($dryRun) {
            $this->warn("Dry run mode - no aliases were actually created");
        }
        
        // Generate sport-specific aliases
        $this->generateSportSpecificAliases($dryRun);
        
        return Command::SUCCESS;
    }
    
    private function generateAliases(string $teamName): array
    {
        $aliases = [];
        
        // Handle State/St. separately (context-sensitive)
        if (preg_match('/\b(State)\b/', $teamName) && !Str::contains($teamName, 'United States')) {
            // Only for universities/colleges
            $aliases[] = preg_replace('/\bState\b/', 'St.', $teamName);
            $aliases[] = preg_replace('/\bState\b/', 'St', $teamName);
        }
        
        // Handle Saint/St.
        if (preg_match('/\bSaint\b/', $teamName)) {
            $aliases[] = preg_replace('/\bSaint\b/', 'St.', $teamName);
            $aliases[] = preg_replace('/\bSaint\b/', 'St', $teamName);
        } elseif (preg_match('/\bSt\.?\b/', $teamName)) {
            $aliases[] = preg_replace('/\bSt\.?\b/', 'Saint', $teamName);
        }
        
        // Apply pattern replacements
        foreach ($this->patterns as $short => $variations) {
            foreach ($variations as $long) {
                // Replace long form with short form
                if (Str::contains($teamName, $long)) {
                    $alias = str_replace($long, $short, $teamName);
                    if ($alias !== $teamName) {
                        $aliases[] = $alias;
                    }
                }
                
                // Replace short form with long forms
                if (Str::contains($teamName, ' ' . $short . ' ') || Str::endsWith($teamName, ' ' . $short)) {
                    foreach ($variations as $replacement) {
                        $alias = preg_replace('/\b' . preg_quote($short) . '\b/', $replacement, $teamName);
                        if ($alias !== $teamName) {
                            $aliases[] = $alias;
                        }
                    }
                }
            }
        }
        
        // Add variations with/without periods in abbreviations
        if (preg_match('/\b[A-Z]\./', $teamName)) {
            // Remove periods from abbreviations
            $aliases[] = preg_replace('/\b([A-Z])\./', '$1', $teamName);
        } elseif (preg_match('/\b[A-Z]{2,}\b/', $teamName)) {
            // Add periods to abbreviations
            $aliases[] = preg_replace_callback('/\b([A-Z]{2,})\b/', function ($matches) {
                return implode('.', str_split($matches[1])) . '.';
            }, $teamName);
        }
        
        // Common misspellings or variations
        $commonVariations = [
            'Athletic' => 'Atletico',
            'Atletico' => 'Athletic',
            'Sporting' => 'Sport',
            'Sport' => 'Sporting',
            'Real' => 'Royal',
            'Royal' => 'Real',
        ];
        
        foreach ($commonVariations as $from => $to) {
            if (Str::contains($teamName, $from)) {
                $alias = str_replace($from, $to, $teamName);
                if ($alias !== $teamName) {
                    $aliases[] = $alias;
                }
            }
        }
        
        return array_unique($aliases);
    }
    
    private function isTooSimilar(string $name1, string $name2): bool
    {
        // If they're identical when normalized, they're too similar
        $normalized1 = strtolower(preg_replace('/[^a-z0-9]/', '', $name1));
        $normalized2 = strtolower(preg_replace('/[^a-z0-9]/', '', $name2));
        
        return $normalized1 === $normalized2;
    }
    
    private function generateSportSpecificAliases(bool $dryRun)
    {
        $this->info("\nGenerating sport-specific aliases...");
        
        // NFL teams often have city abbreviations
        $nflCityAbbreviations = [
            'New York' => ['NY', 'N.Y.'],
            'Los Angeles' => ['LA', 'L.A.'],
            'San Francisco' => ['SF', 'S.F.'],
            'Kansas City' => ['KC', 'K.C.'],
            'New England' => ['NE', 'N.E.'],
            'Tampa Bay' => ['TB', 'T.B.'],
            'Green Bay' => ['GB', 'G.B.'],
            'New Orleans' => ['NO', 'N.O.'],
            'San Diego' => ['SD', 'S.D.'],
            'St. Louis' => ['STL', 'St.L.'],
            'Washington' => ['WAS', 'Wash.'],
        ];
        
        $nflTeams = Team::whereHas('sport', function ($q) {
            $q->where('name', 'LIKE', '%Football%')
                ->orWhere('name', 'NFL');
        })->get();
        
        $created = 0;
        
        foreach ($nflTeams as $team) {
            foreach ($nflCityAbbreviations as $city => $abbreviations) {
                if (Str::startsWith($team->name, $city)) {
                    $nickname = trim(str_replace($city, '', $team->name));
                    
                    foreach ($abbreviations as $abbr) {
                        $alias = $abbr . ' ' . $nickname;
                        
                        // Check if alias already exists
                        if (TeamAlias::where('alias', $alias)->exists()) {
                            continue;
                        }
                        
                        if (!$dryRun) {
                            TeamAlias::create([
                                'team_id' => $team->id,
                                'alias' => $alias,
                            ]);
                        }
                        
                        $this->line("Created city abbreviation alias '{$alias}' for: {$team->name}");
                        $created++;
                    }
                }
            }
        }
        
        if ($created > 0) {
            $this->info("Created {$created} sport-specific aliases");
        }
    }
}