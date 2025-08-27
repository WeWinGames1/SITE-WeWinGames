<?php

namespace Database\Seeders;

use App\Models\Bet;
use App\Models\Operator;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ThreeDayBetsSeeder extends Seeder
{
    public function run(): void
    {
        // Get dates for today, tomorrow, and day after
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $dayAfter = Carbon::tomorrow()->addDay();
        
        // Get or create operators
        $operators = [
            'DraftKings' => Operator::firstOrCreate(['name' => 'DraftKings']),
            'FanDuel' => Operator::firstOrCreate(['name' => 'FanDuel']),
            'BetMGM' => Operator::firstOrCreate(['name' => 'BetMGM']),
            'Caesars' => Operator::firstOrCreate(['name' => 'Caesars']),
            'PointsBet' => Operator::firstOrCreate(['name' => 'PointsBet']),
            'BetRivers' => Operator::firstOrCreate(['name' => 'BetRivers']),
        ];
        
        // Sports and their games/bets for each day
        $dailyBets = [
            $today->format('Y-m-d') => [
                'Football' => [
                    ['home' => 'Kansas City Chiefs', 'away' => 'Buffalo Bills', 'time' => '13:00', 'bets' => 3],
                    ['home' => 'Dallas Cowboys', 'away' => 'Philadelphia Eagles', 'time' => '16:25', 'bets' => 2],
                    ['home' => 'Green Bay Packers', 'away' => 'Chicago Bears', 'time' => '20:20', 'bets' => 3],
                    ['home' => 'Tampa Bay Buccaneers', 'away' => 'New Orleans Saints', 'time' => '13:00', 'bets' => 2],
                    ['home' => 'Miami Dolphins', 'away' => 'New England Patriots', 'time' => '13:00', 'bets' => 2],
                    ['home' => 'San Francisco 49ers', 'away' => 'Seattle Seahawks', 'time' => '16:05', 'bets' => 3],
                    ['home' => 'Baltimore Ravens', 'away' => 'Cincinnati Bengals', 'time' => '13:00', 'bets' => 2],
                    ['home' => 'Detroit Lions', 'away' => 'Minnesota Vikings', 'time' => '13:00', 'bets' => 2],
                ],
                'Basketball' => [
                    ['home' => 'Los Angeles Lakers', 'away' => 'Boston Celtics', 'time' => '19:30', 'bets' => 3],
                    ['home' => 'Golden State Warriors', 'away' => 'Phoenix Suns', 'time' => '22:00', 'bets' => 2],
                    ['home' => 'Milwaukee Bucks', 'away' => 'Brooklyn Nets', 'time' => '19:00', 'bets' => 2],
                    ['home' => 'Denver Nuggets', 'away' => 'Memphis Grizzlies', 'time' => '20:00', 'bets' => 3],
                    ['home' => 'Miami Heat', 'away' => 'Philadelphia 76ers', 'time' => '19:30', 'bets' => 2],
                ],
                'Hockey' => [
                    ['home' => 'Toronto Maple Leafs', 'away' => 'Montreal Canadiens', 'time' => '19:00', 'bets' => 2],
                    ['home' => 'Colorado Avalanche', 'away' => 'Edmonton Oilers', 'time' => '21:00', 'bets' => 3],
                    ['home' => 'New York Rangers', 'away' => 'Boston Bruins', 'time' => '19:30', 'bets' => 2],
                ],
                'Baseball' => [
                    ['home' => 'New York Yankees', 'away' => 'Boston Red Sox', 'time' => '19:05', 'bets' => 2],
                    ['home' => 'Los Angeles Dodgers', 'away' => 'San Francisco Giants', 'time' => '22:10', 'bets' => 2],
                    ['home' => 'Houston Astros', 'away' => 'Texas Rangers', 'time' => '20:10', 'bets' => 1],
                ],
                'Golf' => [
                    ['home' => 'PGA Tour', 'away' => 'The Players Championship', 'time' => '08:00', 'bets' => 4],
                ],
            ],
            $tomorrow->format('Y-m-d') => [
                'Football' => [
                    ['home' => 'Arizona Cardinals', 'away' => 'Los Angeles Rams', 'time' => '16:05', 'bets' => 3],
                    ['home' => 'Tennessee Titans', 'away' => 'Indianapolis Colts', 'time' => '13:00', 'bets' => 2],
                    ['home' => 'Cleveland Browns', 'away' => 'Pittsburgh Steelers', 'time' => '13:00', 'bets' => 3],
                    ['home' => 'Las Vegas Raiders', 'away' => 'Denver Broncos', 'time' => '16:25', 'bets' => 2],
                    ['home' => 'New York Giants', 'away' => 'Washington Commanders', 'time' => '13:00', 'bets' => 2],
                    ['home' => 'Atlanta Falcons', 'away' => 'Carolina Panthers', 'time' => '13:00', 'bets' => 2],
                ],
                'Basketball' => [
                    ['home' => 'Chicago Bulls', 'away' => 'Detroit Pistons', 'time' => '20:00', 'bets' => 2],
                    ['home' => 'Portland Trail Blazers', 'away' => 'Sacramento Kings', 'time' => '22:00', 'bets' => 2],
                    ['home' => 'Orlando Magic', 'away' => 'Charlotte Hornets', 'time' => '19:00', 'bets' => 2],
                    ['home' => 'Utah Jazz', 'away' => 'Oklahoma City Thunder', 'time' => '21:00', 'bets' => 3],
                ],
                'Soccer' => [
                    ['home' => 'Manchester City', 'away' => 'Arsenal', 'time' => '16:30', 'bets' => 3],
                    ['home' => 'Chelsea', 'away' => 'Tottenham', 'time' => '17:30', 'bets' => 2],
                    ['home' => 'AC Milan', 'away' => 'Inter Milan', 'time' => '20:45', 'bets' => 3],
                ],
                'Tennis' => [
                    ['home' => 'ATP Tour', 'away' => 'Miami Open', 'time' => '11:00', 'bets' => 3],
                ],
                'Golf' => [
                    ['home' => 'PGA Tour', 'away' => 'The Players Championship', 'time' => '08:00', 'bets' => 3],
                ],
            ],
            $dayAfter->format('Y-m-d') => [
                'Football' => [
                    ['home' => 'Jacksonville Jaguars', 'away' => 'Houston Texans', 'time' => '13:00', 'bets' => 2],
                    ['home' => 'New York Jets', 'away' => 'Buffalo Bills', 'time' => '20:15', 'bets' => 3],
                    ['home' => 'Los Angeles Chargers', 'away' => 'Kansas City Chiefs', 'time' => '16:25', 'bets' => 3],
                    ['home' => 'Washington Commanders', 'away' => 'Dallas Cowboys', 'time' => '13:00', 'bets' => 2],
                ],
                'Basketball' => [
                    ['home' => 'San Antonio Spurs', 'away' => 'Houston Rockets', 'time' => '20:00', 'bets' => 2],
                    ['home' => 'Indiana Pacers', 'away' => 'Cleveland Cavaliers', 'time' => '19:00', 'bets' => 2],
                    ['home' => 'New Orleans Pelicans', 'away' => 'Dallas Mavericks', 'time' => '20:00', 'bets' => 3],
                    ['home' => 'Toronto Raptors', 'away' => 'Atlanta Hawks', 'time' => '19:30', 'bets' => 2],
                ],
                'Hockey' => [
                    ['home' => 'Pittsburgh Penguins', 'away' => 'Philadelphia Flyers', 'time' => '19:00', 'bets' => 2],
                    ['home' => 'Nashville Predators', 'away' => 'Dallas Stars', 'time' => '20:00', 'bets' => 2],
                    ['home' => 'Calgary Flames', 'away' => 'Vancouver Canucks', 'time' => '21:00', 'bets' => 2],
                ],
                'Golf' => [
                    ['home' => 'PGA Tour', 'away' => 'The Players Championship', 'time' => '08:00', 'bets' => 3],
                ],
                'Ultimate Fighting Championship' => [
                    ['home' => 'UFC Fight Night', 'away' => 'Main Event', 'time' => '22:00', 'bets' => 3],
                ],
            ],
        ];
        
        // Bet types and descriptions
        $betTypes = [
            'spread' => ['Point Spread', 'Against the Spread', 'ATS'],
            'moneyline' => ['Moneyline', 'To Win', 'ML'],
            'total' => ['Over/Under', 'Total Points', 'O/U'],
            'prop' => ['Player Prop', 'Prop Bet', 'Player Performance'],
            'futures' => ['Futures', 'Championship', 'Season Long'],
        ];
        
        // Membership tiers with more premium picks
        $membershipDistribution = [
            'bronze' => 30,  // 30% bronze
            'silver' => 30,  // 30% silver
            'gold' => 25,    // 25% gold
            'platinum' => 15 // 15% platinum
        ];
        
        // Get admin user for bets
        $adminUser = User::where('email', 'admin@wewingames.com')->first();
        if (!$adminUser) {
            $adminUser = User::first();
        }
        
        $totalBetsCreated = 0;
        
        foreach ($dailyBets as $date => $sportsData) {
            $this->command->info("Creating bets for {$date}...");
            
            foreach ($sportsData as $sportName => $games) {
                // Get or create sport
                $sport = Sport::firstOrCreate(['name' => $sportName]);
                
                foreach ($games as $gameData) {
                    // Get or create teams
                    $homeTeam = Team::firstOrCreate(
                        ['name' => $gameData['home']],
                        ['sport_id' => $sport->id]
                    );
                    $awayTeam = Team::firstOrCreate(
                        ['name' => $gameData['away']],
                        ['sport_id' => $sport->id]
                    );
                    
                    // Create specified number of bets per game
                    for ($i = 0; $i < $gameData['bets']; $i++) {
                        $betType = array_rand($betTypes);
                        $betDescription = $betTypes[$betType][array_rand($betTypes[$betType])];
                        $operator = $operators[array_rand($operators)];
                        
                        // Assign membership based on distribution
                        $rand = rand(1, 100);
                        if ($rand <= 30) {
                            $membership = 'bronze';
                        } elseif ($rand <= 60) {
                            $membership = 'silver';
                        } elseif ($rand <= 85) {
                            $membership = 'gold';
                        } else {
                            $membership = 'platinum';
                        }
                        
                        // Generate odds
                        $isPositive = rand(0, 1);
                        $odds = $isPositive ? rand(100, 300) : rand(-300, -100);
                        
                        // Generate pick details based on bet type
                        $pick = $this->generatePick($betType, $homeTeam, $awayTeam, $sportName);
                        
                        // Create analysis
                        $analysis = $this->generateAnalysis($sportName, $homeTeam->name, $awayTeam->name, $pick, $membership);
                        
                        // Create bet
                        Bet::create([
                            'user_id' => $adminUser->id,
                            'sport_id' => $sport->id,
                            'team_one' => $homeTeam->name,
                            'team_one_id' => $homeTeam->id,
                            'team_two' => $awayTeam->name,
                            'team_two_id' => $awayTeam->id,
                            'tips' => $pick,
                            'markets' => $betDescription,
                            'wager_name' => $analysis,
                            'odds' => $odds,
                            'wager_odds' => $odds,
                            'membership' => $membership,
                            'level' => $membership,
                            'status' => 'pending',
                            'game_date' => $date,
                            'betting_date' => $date,
                            'wager_amount' => rand(50, 200),
                            'winning_amount' => 0,
                            'profit_amount' => 0,
                            'sports' => $sportName,
                            'sport' => $sportName,
                            'league' => $this->getLeague($sportName),
                            'game' => "{$homeTeam->name} vs {$awayTeam->name}",
                            'matches' => "{$homeTeam->name} vs {$awayTeam->name}",
                            'referrer' => $operator->name,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        $totalBetsCreated++;
                    }
                }
            }
        }
        
        $this->command->info("Successfully created {$totalBetsCreated} bets across 3 days!");
        $this->command->info("Date breakdown:");
        $this->command->info("- Today ({$today->format('Y-m-d')}): " . array_sum(array_column(array_merge(...array_values($dailyBets[$today->format('Y-m-d')])), 'bets')) . " bets");
        $this->command->info("- Tomorrow ({$tomorrow->format('Y-m-d')}): " . array_sum(array_column(array_merge(...array_values($dailyBets[$tomorrow->format('Y-m-d')])), 'bets')) . " bets");
        $this->command->info("- Day After ({$dayAfter->format('Y-m-d')}): " . array_sum(array_column(array_merge(...array_values($dailyBets[$dayAfter->format('Y-m-d')])), 'bets')) . " bets");
    }
    
    private function generatePick($betType, $homeTeam, $awayTeam, $sport): string
    {
        switch ($betType) {
            case 'spread':
                if ($sport === 'Football' || $sport === 'Basketball') {
                    $spread = rand(1, 14) * 0.5;
                } else {
                    $spread = rand(1, 3) + 0.5;
                }
                return rand(0, 1) ? "{$homeTeam->name} -{$spread}" : "{$awayTeam->name} +{$spread}";
                
            case 'moneyline':
                return rand(0, 1) ? $homeTeam->name : $awayTeam->name;
                
            case 'total':
                $totals = [
                    'Football' => rand(38, 58),
                    'Basketball' => rand(200, 240),
                    'Hockey' => rand(5, 7) + 0.5,
                    'Baseball' => rand(7, 12) + 0.5,
                    'Soccer' => rand(2, 4) + 0.5,
                ];
                $total = $totals[$sport] ?? rand(140, 240);
                return rand(0, 1) ? "Over {$total}" : "Under {$total}";
                
            case 'prop':
                $playerProps = [
                    'Football' => [
                        'players' => ['Mahomes', 'Allen', 'Hurts', 'Prescott', 'Rodgers'],
                        'props' => ['Passing Yards', 'TD Passes', 'Rushing Yards'],
                        'values' => [250, 300, 2.5, 3.5, 50]
                    ],
                    'Basketball' => [
                        'players' => ['LeBron', 'Curry', 'Giannis', 'Jokic', 'Tatum'],
                        'props' => ['Points', 'Rebounds', 'Assists'],
                        'values' => [25.5, 30.5, 8.5, 10.5, 7.5]
                    ],
                    'Hockey' => [
                        'players' => ['McDavid', 'Matthews', 'MacKinnon', 'Ovechkin'],
                        'props' => ['Goals', 'Assists', 'Points'],
                        'values' => [0.5, 1.5, 2.5]
                    ],
                    'Baseball' => [
                        'players' => ['Judge', 'Ohtani', 'Acuna', 'Betts'],
                        'props' => ['Hits', 'RBIs', 'Total Bases'],
                        'values' => [1.5, 2.5, 0.5]
                    ],
                ];
                
                if (isset($playerProps[$sport])) {
                    $sportProps = $playerProps[$sport];
                    $player = $sportProps['players'][array_rand($sportProps['players'])];
                    $prop = $sportProps['props'][array_rand($sportProps['props'])];
                    $value = $sportProps['values'][array_rand($sportProps['values'])];
                    return "{$player} Over {$value} {$prop}";
                }
                return "Player Prop Special";
                
            case 'futures':
                return rand(0, 1) ? "{$homeTeam->name} to Win Division" : "{$awayTeam->name} Championship Odds";
                
            default:
                return rand(0, 1) ? $homeTeam->name : $awayTeam->name;
        }
    }
    
    private function generateAnalysis($sport, $homeTeam, $awayTeam, $pick, $membership): string
    {
        $premiumAnalyses = [
            'gold' => [
                "🔥 PREMIUM PICK: Advanced analytics and insider information strongly favor this selection.",
                "⭐ GOLD MEMBER EXCLUSIVE: Our proprietary model shows 75%+ win probability on this pick.",
                "💎 HIGH CONFIDENCE: Multiple indicators align perfectly for this premium selection.",
            ],
            'platinum' => [
                "🏆 PLATINUM PICK: Our top analysts are unanimous on this elite selection.",
                "💰 BEST BET: Historical data combined with current form makes this our strongest pick today.",
                "🎯 LOCK OF THE DAY: Everything points to this being a can't-miss opportunity.",
            ],
        ];
        
        $regularAnalyses = [
            "Strong momentum and recent performance trends favor this pick.",
            "Statistical analysis shows a clear edge in this matchup.",
            "Key player matchups create a favorable betting opportunity.",
            "Historical head-to-head data supports this selection.",
            "Current form and conditions align well for this pick.",
            "Advanced metrics indicate solid value in this selection.",
            "Defensive/offensive matchups favor this prediction.",
            "Trend analysis points to a profitable opportunity.",
        ];
        
        if (isset($premiumAnalyses[$membership])) {
            $analysis = $premiumAnalyses[$membership][array_rand($premiumAnalyses[$membership])];
        } else {
            $analysis = $regularAnalyses[array_rand($regularAnalyses)];
        }
        
        return $analysis . " {$homeTeam} vs {$awayTeam} - {$pick}";
    }
    
    private function getLeague($sport): string
    {
        $leagues = [
            'Football' => 'NFL',
            'Basketball' => 'NBA',
            'Hockey' => 'NHL',
            'Baseball' => 'MLB',
            'Soccer' => 'Premier League',
            'Golf' => 'PGA Tour',
            'Tennis' => 'ATP',
            'Ultimate Fighting Championship' => 'UFC',
        ];
        
        return $leagues[$sport] ?? $sport;
    }
}