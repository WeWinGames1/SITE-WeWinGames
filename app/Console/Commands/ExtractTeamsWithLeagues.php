<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\League;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ExtractTeamsWithLeagues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bets:extract-teams-with-leagues 
                           {--dry-run : Run without saving to database}
                           {--limit=0 : Limit number of bets to process (0 = all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract sports, leagues (inferred), and teams from existing bets';

    private array $sportsCache = [];

    private array $leaguesCache = [];

    private array $teamsCache = [];

    // League mapping patterns
    private array $leagueMappings = [
        'Football' => [
            'NFL' => [
                'Arizona Cardinals', 'Atlanta Falcons', 'Baltimore Ravens', 'Buffalo Bills',
                'Carolina Panthers', 'Chicago Bears', 'Cincinnati Bengals', 'Cleveland Browns',
                'Dallas Cowboys', 'Denver Broncos', 'Detroit Lions', 'Green Bay Packers',
                'Houston Texans', 'Indianapolis Colts', 'Jacksonville Jaguars', 'Kansas City Chiefs',
                'Las Vegas Raiders', 'Los Angeles Chargers', 'Los Angeles Rams', 'Miami Dolphins',
                'Minnesota Vikings', 'New England Patriots', 'New Orleans Saints', 'New York Giants',
                'New York Jets', 'Philadelphia Eagles', 'Pittsburgh Steelers', 'San Francisco 49ers',
                'Seattle Seahawks', 'Tampa Bay Buccaneers', 'Tennessee Titans', 'Washington Commanders',
                'Oakland Raiders', 'San Diego Chargers', 'St. Louis Rams', 'Washington Redskins',
                'Washington Football Team',
            ],
            'College Football' => [
                // Will match any team with "State", "University", college names, etc.
                'patterns' => ['State', 'University', 'College', 'Tech', 'A&M', 'Bobcats', 'Aztecs',
                    'Wildcats', 'Bulldogs', 'Tigers', 'Eagles', 'Hurricanes', 'Seminoles'],
            ],
        ],
        'Basketball' => [
            'NBA' => [
                'Atlanta Hawks', 'Boston Celtics', 'Brooklyn Nets', 'Charlotte Hornets',
                'Chicago Bulls', 'Cleveland Cavaliers', 'Dallas Mavericks', 'Denver Nuggets',
                'Detroit Pistons', 'Golden State Warriors', 'Houston Rockets', 'Indiana Pacers',
                'LA Clippers', 'Los Angeles Clippers', 'Los Angeles Lakers', 'Memphis Grizzlies',
                'Miami Heat', 'Milwaukee Bucks', 'Minnesota Timberwolves', 'New Orleans Pelicans',
                'New York Knicks', 'Oklahoma City Thunder', 'Orlando Magic', 'Philadelphia 76ers',
                'Phoenix Suns', 'Portland Trail Blazers', 'Sacramento Kings', 'San Antonio Spurs',
                'Toronto Raptors', 'Utah Jazz', 'Washington Wizards',
            ],
            'College Basketball' => [
                'patterns' => ['State', 'University', 'College', 'Tech', 'A&M'],
            ],
        ],
        'Hockey' => [
            'NHL' => [
                'Anaheim Ducks', 'Arizona Coyotes', 'Boston Bruins', 'Buffalo Sabres',
                'Calgary Flames', 'Carolina Hurricanes', 'Chicago Blackhawks', 'Colorado Avalanche',
                'Columbus Blue Jackets', 'Dallas Stars', 'Detroit Red Wings', 'Edmonton Oilers',
                'Florida Panthers', 'Los Angeles Kings', 'Minnesota Wild', 'Montreal Canadiens',
                'Nashville Predators', 'New Jersey Devils', 'New York Islanders', 'New York Rangers',
                'Ottawa Senators', 'Philadelphia Flyers', 'Pittsburgh Penguins', 'San Jose Sharks',
                'Seattle Kraken', 'St. Louis Blues', 'Tampa Bay Lightning', 'Toronto Maple Leafs',
                'Vancouver Canucks', 'Vegas Golden Knights', 'Washington Capitals', 'Winnipeg Jets',
            ],
        ],
        'Baseball' => [
            'MLB' => [
                'Arizona Diamondbacks', 'Atlanta Braves', 'Baltimore Orioles', 'Boston Red Sox',
                'Chicago Cubs', 'Chicago White Sox', 'Cincinnati Reds', 'Cleveland Guardians',
                'Colorado Rockies', 'Detroit Tigers', 'Houston Astros', 'Kansas City Royals',
                'Los Angeles Angels', 'Los Angeles Dodgers', 'Miami Marlins', 'Milwaukee Brewers',
                'Minnesota Twins', 'New York Mets', 'New York Yankees', 'Oakland Athletics',
                'Philadelphia Phillies', 'Pittsburgh Pirates', 'San Diego Padres', 'San Francisco Giants',
                'Seattle Mariners', 'St. Louis Cardinals', 'Tampa Bay Rays', 'Texas Rangers',
                'Toronto Blue Jays', 'Washington Nationals', 'Cleveland Indians',
            ],
        ],
        'Soccer' => [
            'Premier League' => [
                'Arsenal', 'Aston Villa', 'Bournemouth', 'Brentford', 'Brighton', 'Brighton & Hove Albion',
                'Burnley', 'Chelsea', 'Crystal Palace', 'Everton', 'Fulham', 'Leeds United',
                'Leicester City', 'Liverpool', 'Luton Town', 'Manchester City', 'Manchester United',
                'Newcastle United', 'Nottingham Forest', 'Sheffield United', 'Tottenham Hotspur',
                'West Ham United', 'Wolverhampton Wanderers', 'Wolves',
            ],
            'MLS' => [
                'patterns' => ['FC', 'United', 'City FC', 'SC', 'Sounders', 'Galaxy', 'Timbers', 'Crew'],
            ],
            'International' => [
                'patterns' => ['United States', 'Mexico', 'Canada', 'Brazil', 'Argentina', 'England', 'Spain', 'Germany', 'France', 'Italy'],
            ],
        ],
        'UFC' => [
            'UFC' => [
                'patterns' => ['vs'], // All UFC bets are individual matchups
            ],
        ],
        'Tennis' => [
            'ATP Tour' => [
                'patterns' => ['vs'], // All tennis matches are individual matchups
            ],
        ],
        'Golf' => [
            'PGA Tour' => [
                'patterns' => ['Tournament', 'Open', 'Championship', 'Masters', 'Classic'],
            ],
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Starting extraction of sports, leagues (inferred), and teams from bets...');

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

                // Extract teams and infer leagues
                $teams = $this->extractTeamsFromBet($bet);

                foreach ($teams as $teamData) {
                    $teamName = $this->normalizeTeamName($teamData['name']);
                    if (! $teamName) {
                        continue;
                    }

                    // Infer league based on team name and sport
                    $leagueName = $this->inferLeague($teamName, $sportName);

                    // Create league if needed
                    if ($leagueName && $sportName) {
                        $cacheKey = "{$sportName}:{$leagueName}";

                        if (! isset($this->leaguesCache[$cacheKey])) {
                            $sportId = $this->sportsCache[$sportName] ?? null;

                            if ($sportId && ! $dryRun) {
                                // Check if league already exists
                                $league = League::where('name', $leagueName)
                                    ->where('sport_id', $sportId)
                                    ->first();

                                if (! $league) {
                                    // Generate unique slug
                                    $baseSlug = Str::slug($leagueName);
                                    $slug = $baseSlug;
                                    $counter = 1;

                                    while (League::where('slug', $slug)->exists()) {
                                        // Add sport name to make it unique
                                        $sportForSlug = array_search($sportId, $this->sportsCache) ?: '';
                                        $slug = $baseSlug.'-'.Str::slug($sportForSlug);

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

                                    if ($league->wasRecentlyCreated) {
                                        $stats['leagues_created']++;
                                    }
                                }

                                $this->leaguesCache[$cacheKey] = $league->id;
                            } else {
                                $this->leaguesCache[$cacheKey] = 'dry-run-id';
                                $stats['leagues_created']++;
                            }
                        }
                    }

                    // Create team
                    $sportId = isset($this->sportsCache[$sportName]) ? $this->sportsCache[$sportName] : null;
                    $leagueId = null;

                    if ($leagueName && $sportName) {
                        $cacheKey = "{$sportName}:{$leagueName}";
                        $leagueId = isset($this->leaguesCache[$cacheKey]) ? $this->leaguesCache[$cacheKey] : null;
                    }

                    $teamCacheKey = "{$sportName}:{$leagueName}:{$teamName}";

                    if ($sportId && ! isset($this->teamsCache[$teamCacheKey])) {
                        if (! $dryRun && $sportId !== 'dry-run-id') {
                            // Check if team already exists
                            $team = Team::where('name', $teamName)
                                ->where('sport_id', $sportId)
                                ->where('league_id', $leagueId !== 'dry-run-id' ? $leagueId : null)
                                ->first();

                            if (! $team) {
                                // Generate unique slug
                                $baseSlug = Str::slug($teamName);
                                $slug = $baseSlug;
                                $counter = 1;

                                while (Team::where('slug', $slug)->exists()) {
                                    $slug = $baseSlug.'-'.$counter;
                                    $counter++;
                                }

                                $team = Team::create([
                                    'name' => $teamName,
                                    'sport_id' => $sportId,
                                    'league_id' => $leagueId !== 'dry-run-id' ? $leagueId : null,
                                    'slug' => $slug,
                                    'is_active' => true,
                                ]);

                                if ($team->wasRecentlyCreated) {
                                    $stats['teams_created']++;
                                }
                            }

                            $this->teamsCache[$teamCacheKey] = $team->id;
                        } else {
                            $this->teamsCache[$teamCacheKey] = 'dry-run-id';
                            $stats['teams_created']++;
                        }
                    }

                    // Update bet with team relationship
                    if (! $dryRun && isset($this->teamsCache[$teamCacheKey]) && $this->teamsCache[$teamCacheKey] !== 'dry-run-id') {
                        if ($teamData['position'] === 'team_one') {
                            $bet->team_one_id = $this->teamsCache[$teamCacheKey];
                        } elseif ($teamData['position'] === 'team_two') {
                            $bet->team_two_id = $this->teamsCache[$teamCacheKey];
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

    private function inferLeague(string $teamName, string $sportName): ?string
    {
        if (! isset($this->leagueMappings[$sportName])) {
            return 'General'; // Default league for unknown sports
        }

        foreach ($this->leagueMappings[$sportName] as $leagueName => $teams) {
            // Check exact matches
            if (is_array($teams) && ! isset($teams['patterns'])) {
                foreach ($teams as $team) {
                    if (strcasecmp($teamName, $team) === 0 ||
                        stripos($teamName, $team) !== false) {
                        return $leagueName;
                    }
                }
            }

            // Check pattern matches
            if (isset($teams['patterns'])) {
                foreach ($teams['patterns'] as $pattern) {
                    if (stripos($teamName, $pattern) !== false) {
                        return $leagueName;
                    }
                }
            }
        }

        // Special handling for specific sports
        if ($sportName === 'UFC' || $sportName === 'MMA') {
            return 'UFC';
        }

        if ($sportName === 'Tennis') {
            return 'ATP Tour';
        }

        if ($sportName === 'Golf') {
            return 'PGA Tour';
        }

        // Default leagues by sport
        $defaultLeagues = [
            'Football' => 'NFL',
            'Basketball' => 'NBA',
            'Hockey' => 'NHL',
            'Baseball' => 'MLB',
            'Soccer' => 'International',
        ];

        return $defaultLeagues[$sportName] ?? 'General';
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
            $leagueName = $team->league ? $team->league->name : 'General';
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

        return $teams;
    }

    private function normalizeSportName(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        $name = trim($name);

        // Map common variations
        $sportMappings = [
            'ufc' => 'UFC',
            'mma' => 'MMA',
            'mixed martial arts' => 'MMA',
            'nfl' => 'Football',
            'college football' => 'Football',
            'ncaaf' => 'Football',
            'nba' => 'Basketball',
            'college basketball' => 'Basketball',
            'ncaab' => 'Basketball',
            'mlb' => 'Baseball',
            'nhl' => 'Hockey',
            'soccer' => 'Soccer',
            'football (soccer)' => 'Soccer',
            'tennis' => 'Tennis',
            'golf' => 'Golf',
        ];

        $lower = strtolower($name);
        if (isset($sportMappings[$lower])) {
            return $sportMappings[$lower];
        }

        // Default formatting
        return ucwords(strtolower($name));
    }

    private function normalizeTeamName(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        // Remove number prefixes like "1. "
        $name = preg_replace('/^\d+\.\s+/', '', $name);

        // Remove betting odds or modifiers in parentheses
        $name = preg_replace('/\s*\([^)]+\)\s*$/', '', $name);

        return trim($name);
    }
}
