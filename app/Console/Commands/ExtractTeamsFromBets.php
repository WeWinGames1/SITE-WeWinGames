<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\League;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ExtractTeamsFromBets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bets:extract-teams 
                           {--dry-run : Run without saving to database}
                           {--limit=0 : Limit number of bets to process (0 = all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract sports, leagues, and teams from existing bets and populate the database';

    private array $sportsCache = [];

    private array $leaguesCache = [];

    private array $teamsCache = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Starting extraction of sports, leagues, and teams from bets...');

        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode - no data will be saved.');
        }

        // Load existing data into cache
        $this->loadExistingData();

        // Query bets
        $query = Bet::query()
            ->whereNotNull('sports')
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $totalBets = $query->count();
        $this->info("Processing {$totalBets} bets...");

        $bar = $this->output->createProgressBar($totalBets);
        $bar->start();

        $stats = [
            'sports_created' => 0,
            'leagues_created' => 0,
            'teams_created' => 0,
            'bets_updated' => 0,
        ];

        $query->chunk(100, function ($bets) use ($dryRun, $bar, &$stats) {
            foreach ($bets as $bet) {
                // Extract sport
                $sportName = $this->normalizeSportName($bet->sports);
                if ($sportName && ! isset($this->sportsCache[$sportName])) {
                    if (! $dryRun) {
                        $sport = Sport::firstOrCreate(
                            ['name' => $sportName],
                            [
                                'slug' => Str::slug($sportName),
                                'is_active' => true,
                            ]
                        );
                        $this->sportsCache[$sportName] = $sport->id;
                        if ($sport->wasRecentlyCreated) {
                            $stats['sports_created']++;
                        }
                    } else {
                        $this->sportsCache[$sportName] = 'dry-run-id';
                        $stats['sports_created']++;
                    }
                }

                // Extract league
                if ($bet->league && $sportName) {
                    $leagueName = $this->normalizeLeagueName($bet->league);
                    $cacheKey = "{$sportName}:{$leagueName}";

                    if ($leagueName && ! isset($this->leaguesCache[$cacheKey])) {
                        $sportId = $this->sportsCache[$sportName] ?? null;

                        if ($sportId && ! $dryRun) {
                            // First check if league exists with this name in this sport
                            $league = League::where('name', $leagueName)
                                ->where('sport_id', $sportId)
                                ->first();

                            if (! $league) {
                                // Generate a unique slug
                                $baseSlug = Str::slug($leagueName);
                                $slug = $baseSlug;
                                $counter = 1;

                                while (League::where('slug', $slug)->exists()) {
                                    // Add sport name to make it unique
                                    $sportNameForSlug = $this->sportsCache ? array_search($sportId, $this->sportsCache) : '';
                                    $slug = $baseSlug.'-'.Str::slug($sportNameForSlug);

                                    // If still not unique, add counter
                                    if (League::where('slug', $slug)->exists()) {
                                        $slug = $baseSlug.'-'.$counter;
                                        $counter++;
                                    }
                                }

                                $league = League::create([
                                    'name' => $leagueName,
                                    'sport_id' => $sportId,
                                    'slug' => $slug,
                                    'is_active' => true,
                                ]);
                                $stats['leagues_created']++;
                            }

                            $this->leaguesCache[$cacheKey] = $league->id;
                        } else {
                            $this->leaguesCache[$cacheKey] = 'dry-run-id';
                            $stats['leagues_created']++;
                        }
                    }
                }

                // Extract teams
                $teams = $this->extractTeamsFromBet($bet);
                foreach ($teams as $teamData) {
                    $teamName = $this->normalizeTeamName($teamData['name']);
                    if (! $teamName) {
                        continue;
                    }

                    $sportId = isset($this->sportsCache[$sportName]) ? $this->sportsCache[$sportName] : null;
                    $leagueId = null;

                    if ($bet->league && $sportName) {
                        $leagueName = $this->normalizeLeagueName($bet->league);
                        $cacheKey = "{$sportName}:{$leagueName}";
                        $leagueId = isset($this->leaguesCache[$cacheKey]) ? $this->leaguesCache[$cacheKey] : null;
                    }

                    $leagueName = $this->normalizeLeagueName($bet->league);
                    $cacheKey = "{$sportName}:{$leagueName}:{$teamName}";

                    if ($sportId) {
                        // Check if we already have this team in cache
                        if (! isset($this->teamsCache[$cacheKey])) {
                            if (! $dryRun && $sportId !== 'dry-run-id') {
                                // First check if team exists with this name in this sport/league
                                $team = Team::where('name', $teamName)
                                    ->where('sport_id', $sportId)
                                    ->where('league_id', $leagueId !== 'dry-run-id' ? $leagueId : null)
                                    ->first();

                                if (! $team) {
                                    // Generate a unique slug
                                    $baseSlug = Str::slug($teamName);
                                    $slug = $baseSlug;
                                    $counter = 1;

                                    while (Team::where('slug', $slug)->exists()) {
                                        // Add sport name to make it unique
                                        $sportName = $this->sportsCache ? array_search($sportId, $this->sportsCache) : '';
                                        $slug = $baseSlug.'-'.Str::slug($sportName);

                                        // If still not unique, add counter
                                        if (Team::where('slug', $slug)->exists()) {
                                            $slug = $baseSlug.'-'.$counter;
                                            $counter++;
                                        }
                                    }

                                    $team = Team::create([
                                        'name' => $teamName,
                                        'sport_id' => $sportId,
                                        'league_id' => $leagueId !== 'dry-run-id' ? $leagueId : null,
                                        'slug' => $slug,
                                        'is_active' => true,
                                    ]);
                                    $stats['teams_created']++;
                                }

                                $this->teamsCache[$cacheKey] = $team->id;
                            } else {
                                $this->teamsCache[$cacheKey] = 'dry-run-id';
                                $stats['teams_created']++;
                            }
                        }

                        // Update bet with team relationship using cached team ID
                        if (! $dryRun && isset($this->teamsCache[$cacheKey]) && $this->teamsCache[$cacheKey] !== 'dry-run-id') {
                            if ($teamData['position'] === 'team_one') {
                                $bet->team_one_id = $this->teamsCache[$cacheKey];
                            } elseif ($teamData['position'] === 'team_two') {
                                $bet->team_two_id = $this->teamsCache[$cacheKey];
                            }
                        }
                    }
                }

                // Save bet updates
                if (! $dryRun && ($bet->team_one_id || $bet->team_two_id)) {
                    $bet->save();
                    $stats['bets_updated']++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        // Display results
        $this->info('Extraction complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Sports Created', $stats['sports_created']],
                ['Leagues Created', $stats['leagues_created']],
                ['Teams Created', $stats['teams_created']],
                ['Bets Updated', $stats['bets_updated']],
            ]
        );

        return Command::SUCCESS;
    }

    private function loadExistingData(): void
    {
        // Load sports
        Sport::all()->each(function ($sport) {
            $this->sportsCache[$sport->name] = $sport->id;
        });

        // Load leagues
        League::with('sport')->get()->each(function ($league) {
            $cacheKey = "{$league->sport->name}:{$league->name}";
            $this->leaguesCache[$cacheKey] = $league->id;
        });

        // Load teams
        Team::with(['sport', 'league'])->get()->each(function ($team) {
            $leagueName = $team->league ? $team->league->name : null;
            $cacheKey = "{$team->sport->name}:{$leagueName}:{$team->name}";
            $this->teamsCache[$cacheKey] = $team->id;
        });
    }

    private function extractTeamsFromBet(Bet $bet): array
    {
        $teams = [];

        if ($bet->team_one) {
            $teams[] = [
                'name' => $bet->team_one,
                'position' => 'team_one',
            ];
        }

        if ($bet->team_two) {
            $teams[] = [
                'name' => $bet->team_two,
                'position' => 'team_two',
            ];
        }

        // TODO: Handle parlays when we implement them
        // For now, just extract from standard team fields

        return $teams;
    }

    private function normalizeSportName(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        $name = trim($name);

        // Special cases
        $specialCases = [
            'ufc' => 'UFC',
            'mma' => 'MMA',
            'nfl' => 'NFL',
            'nba' => 'NBA',
            'mlb' => 'MLB',
            'nhl' => 'NHL',
        ];

        $lower = strtolower($name);
        if (isset($specialCases[$lower])) {
            return $specialCases[$lower];
        }

        return ucwords(strtolower($name));
    }

    private function normalizeLeagueName(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        return trim($name);
    }

    private function normalizeTeamName(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        // Remove number prefixes like "1. "
        $name = preg_replace('/^\d+\.\s+/', '', $name);

        return trim($name);
    }
}
