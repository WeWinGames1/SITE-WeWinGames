<?php

namespace Database\Seeders;

use App\Models\Bet;
use App\Models\Operator;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TodaysBetsSeeder extends Seeder
{
    public function run(): void
    {
        // Get today's date
        $today = Carbon::today();

        // Get or create operators
        $operators = [
            'DraftKings' => Operator::firstOrCreate(['name' => 'DraftKings']),
            'FanDuel' => Operator::firstOrCreate(['name' => 'FanDuel']),
            'BetMGM' => Operator::firstOrCreate(['name' => 'BetMGM']),
            'Caesars' => Operator::firstOrCreate(['name' => 'Caesars']),
        ];

        // Sports and their games/bets for today
        $sportsData = [
            'Football' => [
                ['home' => 'Kansas City Chiefs', 'away' => 'Buffalo Bills', 'time' => '13:00'],
                ['home' => 'Dallas Cowboys', 'away' => 'Philadelphia Eagles', 'time' => '16:25'],
                ['home' => 'Green Bay Packers', 'away' => 'Chicago Bears', 'time' => '20:20'],
                ['home' => 'Tampa Bay Buccaneers', 'away' => 'New Orleans Saints', 'time' => '13:00'],
                ['home' => 'Miami Dolphins', 'away' => 'New England Patriots', 'time' => '13:00'],
                ['home' => 'San Francisco 49ers', 'away' => 'Seattle Seahawks', 'time' => '16:05'],
            ],
            'Basketball' => [
                ['home' => 'Los Angeles Lakers', 'away' => 'Boston Celtics', 'time' => '19:30'],
                ['home' => 'Golden State Warriors', 'away' => 'Phoenix Suns', 'time' => '22:00'],
                ['home' => 'Milwaukee Bucks', 'away' => 'Brooklyn Nets', 'time' => '19:00'],
                ['home' => 'Denver Nuggets', 'away' => 'Memphis Grizzlies', 'time' => '20:00'],
                ['home' => 'Miami Heat', 'away' => 'Philadelphia 76ers', 'time' => '19:30'],
                ['home' => 'Dallas Mavericks', 'away' => 'San Antonio Spurs', 'time' => '20:30'],
            ],
            'Hockey' => [
                ['home' => 'Toronto Maple Leafs', 'away' => 'Montreal Canadiens', 'time' => '19:00'],
                ['home' => 'Colorado Avalanche', 'away' => 'Edmonton Oilers', 'time' => '21:00'],
                ['home' => 'New York Rangers', 'away' => 'Boston Bruins', 'time' => '19:30'],
                ['home' => 'Tampa Bay Lightning', 'away' => 'Florida Panthers', 'time' => '19:00'],
                ['home' => 'Vegas Golden Knights', 'away' => 'Seattle Kraken', 'time' => '22:00'],
            ],
            'Baseball' => [
                ['home' => 'New York Yankees', 'away' => 'Boston Red Sox', 'time' => '19:05'],
                ['home' => 'Los Angeles Dodgers', 'away' => 'San Francisco Giants', 'time' => '22:10'],
                ['home' => 'Houston Astros', 'away' => 'Texas Rangers', 'time' => '20:10'],
                ['home' => 'Atlanta Braves', 'away' => 'Philadelphia Phillies', 'time' => '19:20'],
                ['home' => 'Chicago Cubs', 'away' => 'St. Louis Cardinals', 'time' => '14:20'],
            ],
            'Soccer' => [
                ['home' => 'Manchester United', 'away' => 'Liverpool FC', 'time' => '15:00'],
                ['home' => 'Real Madrid', 'away' => 'Barcelona', 'time' => '20:00'],
                ['home' => 'Bayern Munich', 'away' => 'Borussia Dortmund', 'time' => '18:30'],
                ['home' => 'Paris Saint-Germain', 'away' => 'Marseille', 'time' => '21:00'],
            ],
            'Golf' => [
                ['home' => 'PGA Tour', 'away' => 'The Masters', 'time' => '08:00'],
                ['home' => 'PGA Tour', 'away' => 'US Open', 'time' => '07:30'],
            ],
            'Ultimate Fighting Championship' => [
                ['home' => 'UFC 298', 'away' => 'Main Card', 'time' => '22:00'],
                ['home' => 'UFC Fight Night', 'away' => 'Prelims', 'time' => '18:00'],
            ],
        ];

        // Bet types and descriptions
        $betTypes = [
            'spread' => ['Point Spread', 'Against the Spread'],
            'moneyline' => ['Moneyline', 'To Win'],
            'total' => ['Over/Under', 'Total Points'],
            'prop' => ['Player Prop', 'Prop Bet'],
        ];

        // Membership tiers distribution
        $memberships = ['bronze', 'bronze', 'bronze', 'silver', 'silver', 'gold', 'gold', 'platinum'];

        // Get admin user for bets
        $adminUser = User::where('email', 'admin@wewingames.com')->first();
        if (! $adminUser) {
            $adminUser = User::first();
        }

        $betId = 1000; // Start with a high ID to avoid conflicts

        foreach ($sportsData as $sportName => $games) {
            // Get or create sport
            $sport = Sport::firstOrCreate(['name' => $sportName]);

            $betIndex = 0;
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

                // Create 1-3 bets per game
                $numBets = rand(1, 3);
                for ($i = 0; $i < $numBets; $i++) {
                    $betType = array_rand($betTypes);
                    $betDescription = $betTypes[$betType][array_rand($betTypes[$betType])];
                    $operator = $operators[array_rand($operators)];
                    $membership = $memberships[$betIndex % count($memberships)];

                    // Generate odds
                    $isPositive = rand(0, 1);
                    $odds = $isPositive ? rand(100, 300) : rand(-300, -100);

                    // Generate pick details based on bet type
                    $pick = '';
                    switch ($betType) {
                        case 'spread':
                            $spread = rand(1, 14) * 0.5;
                            $pick = rand(0, 1) ? "{$homeTeam->name} -{$spread}" : "{$awayTeam->name} +{$spread}";
                            break;
                        case 'moneyline':
                            $pick = rand(0, 1) ? $homeTeam->name : $awayTeam->name;
                            break;
                        case 'total':
                            $total = rand(140, 240);
                            $pick = rand(0, 1) ? "Over {$total}" : "Under {$total}";
                            break;
                        case 'prop':
                            $playerNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones'];
                            $propTypes = ['Points', 'Rebounds', 'Assists', 'Yards', 'Goals'];
                            $player = $playerNames[array_rand($playerNames)];
                            $propType = $propTypes[array_rand($propTypes)];
                            $value = rand(10, 50);
                            $pick = "{$player} Over {$value} {$propType}";
                            break;
                    }

                    // Create analysis
                    $analysis = $this->generateAnalysis($sportName, $homeTeam->name, $awayTeam->name, $pick);

                    // Create bet directly without game reference
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
                        'game_date' => $today->format('Y-m-d'),
                        'betting_date' => $today->format('Y-m-d'),
                        'wager_amount' => rand(50, 200),
                        'winning_amount' => 0,
                        'profit_amount' => 0,
                        'sports' => $sportName,
                        'sport' => $sportName,
                        'league' => $this->getLeague($sportName),
                        'game' => "{$homeTeam->name} vs {$awayTeam->name}",
                        'matches' => "{$homeTeam->name} vs {$awayTeam->name}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $betIndex++;
                    $betId++;
                }
            }
        }

        $this->command->info('Today\'s bets seeded successfully!');
    }

    private function generateAnalysis($sport, $homeTeam, $awayTeam, $pick): string
    {
        $analyses = [
            'Strong momentum favors this pick with recent performance trends.',
            'Statistical analysis shows a clear edge in this matchup.',
            'Key player matchups favor this selection significantly.',
            'Historical data supports this betting opportunity.',
            'Current form and conditions align perfectly for this pick.',
            'Advanced metrics indicate high value in this selection.',
            'Defensive/offensive matchups create a favorable situation.',
            'Recent head-to-head results support this prediction.',
        ];

        return $analyses[array_rand($analyses)]." {$homeTeam} vs {$awayTeam} presents an excellent opportunity.";
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
            'Ultimate Fighting Championship' => 'UFC',
        ];

        return $leagues[$sport] ?? $sport;
    }
}
