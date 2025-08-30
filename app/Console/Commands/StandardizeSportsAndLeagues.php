<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\League;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Console\Command;

class StandardizeSportsAndLeagues extends Command
{
    protected $signature = 'teams:standardize';

    protected $description = 'Standardize sport names and create/assign leagues based on bet data';

    public function handle()
    {
        $this->info('Standardizing Sports and Leagues...');
        $this->newLine();

        // Step 1: Standardize sport names
        $this->standardizeSportNames();

        // Step 2: Create leagues from bet data
        $this->createLeaguesFromBets();

        // Step 3: Assign teams to leagues based on bet data
        $this->assignTeamsToLeagues();

        // Step 4: Final report
        $this->generateReport();

        return Command::SUCCESS;
    }

    private function standardizeSportNames()
    {
        $this->info('Step 1: Standardizing sport names...');

        // Map lowercase/variant names to standard names
        $sportMapping = [
            'basketball' => 'Basketball',
            'soccer' => 'Soccer',
            'football' => 'Football',
            'hockey' => 'Hockey',
            'baseball' => 'Baseball',
            'golf' => 'Golf',
            'combat sports' => 'Combat Sports',
            'other' => 'Other Sports',
            'other sports' => 'Other Sports',
        ];

        foreach ($sportMapping as $variant => $standard) {
            // Find the standard sport
            $standardSport = Sport::where('name', $standard)->first();
            if (! $standardSport) {
                $standardSport = Sport::create([
                    'name' => $standard,
                    'slug' => \Str::slug($standard),
                    'is_active' => true,
                ]);
            }

            // Find variant sport
            $variantSport = Sport::where('name', $variant)->first();
            if ($variantSport && $variantSport->id !== $standardSport->id) {
                // Update all teams
                $updated = Team::where('sport_id', $variantSport->id)
                    ->update(['sport_id' => $standardSport->id]);

                $this->line("  Updated {$updated} teams from '{$variant}' to '{$standard}'");

                // Delete the variant sport
                $variantSport->delete();
            }
        }

        $this->info('✅ Sport names standardized');
        $this->newLine();
    }

    private function createLeaguesFromBets()
    {
        $this->info('Step 2: Creating leagues from bet data...');

        // Get unique sport/league combinations from bets
        $leagues = Bet::select('sports', 'league')
            ->whereNotNull('league')
            ->where('league', '!=', '')
            ->groupBy('sports', 'league')
            ->get();

        $createdCount = 0;

        foreach ($leagues as $leagueData) {
            // Find the sport
            $sport = Sport::where('name', $leagueData->sports)->first();
            if (! $sport) {
                continue;
            }

            // Check if league exists
            $league = League::where('name', $leagueData->league)
                ->where('sport_id', $sport->id)
                ->first();

            if (! $league) {
                // Determine abbreviation
                $abbreviation = $this->getLeagueAbbreviation($leagueData->league);

                League::create([
                    'name' => $leagueData->league,
                    'sport_id' => $sport->id,
                    'abbreviation' => $abbreviation,
                    'slug' => \Str::slug($leagueData->league),
                    'is_active' => true,
                ]);
                $createdCount++;
            }
        }

        $this->info("✅ Created {$createdCount} leagues from bet data");
        $this->newLine();
    }

    private function assignTeamsToLeagues()
    {
        $this->info('Step 3: Assigning teams to leagues based on bet data...');

        // Process each sport
        $sports = Sport::all();
        $totalAssigned = 0;

        foreach ($sports as $sport) {
            $assigned = 0;

            // Get teams without leagues for this sport
            $teamsWithoutLeague = Team::where('sport_id', $sport->id)
                ->whereNull('league_id')
                ->get();

            foreach ($teamsWithoutLeague as $team) {
                // Find bets with this team
                $bet = Bet::where('sports', $sport->name)
                    ->where(function ($q) use ($team) {
                        $q->where('team_one_id', $team->id)
                            ->orWhere('team_two_id', $team->id);
                    })
                    ->whereNotNull('league')
                    ->where('league', '!=', '')
                    ->first();

                if ($bet) {
                    // Find the league
                    $league = League::where('name', $bet->league)
                        ->where('sport_id', $sport->id)
                        ->first();

                    if ($league) {
                        $team->update(['league_id' => $league->id]);
                        $assigned++;
                        $totalAssigned++;
                    }
                }
            }

            if ($assigned > 0) {
                $this->line("  {$sport->name}: Assigned {$assigned} teams to leagues");
            }
        }

        $this->info("✅ Assigned {$totalAssigned} teams to leagues");
        $this->newLine();
    }

    private function getLeagueAbbreviation($leagueName)
    {
        // Common abbreviations
        $abbreviations = [
            'National Hockey League' => 'NHL',
            'National Basketball Association' => 'NBA',
            'National Football League' => 'NFL',
            'Major League Baseball' => 'MLB',
            'Major League Soccer' => 'MLS',
            'Premier League' => 'EPL',
            'English Premier League' => 'EPL',
            'Ultimate Fighting Championship' => 'UFC',
            'Professional Golfers Association' => 'PGA',
            'Ladies Professional Golf Association' => 'LPGA',
            'UEFA Champions League' => 'UCL',
            'UEFA Europa League' => 'UEL',
            'La Liga' => 'LaLiga',
            'Serie A' => 'SerieA',
            'Bundesliga' => 'BL',
            'Ligue 1' => 'L1',
        ];

        if (isset($abbreviations[$leagueName])) {
            return $abbreviations[$leagueName];
        }

        // Check if the name itself looks like an abbreviation
        if (strlen($leagueName) <= 5 && strtoupper($leagueName) === $leagueName) {
            return $leagueName;
        }

        // Generate abbreviation from first letters
        $words = explode(' ', $leagueName);
        $abbr = '';
        foreach ($words as $word) {
            if (strlen($word) > 2) { // Skip small words
                $abbr .= strtoupper(substr($word, 0, 1));
            }
        }

        return $abbr ?: strtoupper(substr($leagueName, 0, 3));
    }

    private function generateReport()
    {
        $this->info('Final Report');
        $this->info('============');

        $totalTeams = Team::count();
        $teamsWithLeague = Team::whereNotNull('league_id')->count();
        $percentage = round(($teamsWithLeague / $totalTeams) * 100, 2);

        $this->line('Total teams: '.number_format($totalTeams));
        $this->line('Teams with league: '.number_format($teamsWithLeague));
        $this->line('Teams without league: '.number_format($totalTeams - $teamsWithLeague));
        $this->line("League assignment rate: {$percentage}%");

        $this->newLine();

        // Show by sport
        $this->info('Teams by Sport:');
        $sports = Sport::withCount(['teams', 'teams as teams_with_league' => function ($q) {
            $q->whereNotNull('league_id');
        }])->get();

        $tableData = [];
        foreach ($sports as $sport) {
            $pct = $sport->teams_count > 0
                ? round(($sport->teams_with_league / $sport->teams_count) * 100, 2)
                : 0;

            $tableData[] = [
                $sport->name,
                number_format($sport->teams_count),
                number_format($sport->teams_with_league),
                "{$pct}%",
            ];
        }

        $this->table(['Sport', 'Total Teams', 'With League', 'Percentage'], $tableData);
    }
}
