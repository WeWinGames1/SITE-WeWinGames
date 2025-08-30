<?php

namespace Database\Seeders;

use App\Models\Bet;
use App\Models\Operator;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TomorrowsBetsSeeder extends Seeder
{
    public function run(): void
    {
        // Get tomorrow's date
        $tomorrow = Carbon::tomorrow();

        // Get operators
        $operators = Operator::all();
        if ($operators->isEmpty()) {
            $operators = collect([
                Operator::create(['name' => 'DraftKings']),
                Operator::create(['name' => 'FanDuel']),
                Operator::create(['name' => 'BetMGM']),
                Operator::create(['name' => 'Caesars']),
            ]);
        }

        // Sports and their games/bets for tomorrow
        $sportsData = [
            'Football' => [
                ['home' => 'New York Giants', 'away' => 'Washington Commanders', 'time' => '13:00', 'membership' => 'bronze'],
                ['home' => 'Denver Broncos', 'away' => 'Las Vegas Raiders', 'time' => '16:05', 'membership' => 'bronze'],
                ['home' => 'Arizona Cardinals', 'away' => 'Los Angeles Rams', 'time' => '16:25', 'membership' => 'silver'],
                ['home' => 'Pittsburgh Steelers', 'away' => 'Baltimore Ravens', 'time' => '20:20', 'membership' => 'gold'],
                ['home' => 'Detroit Lions', 'away' => 'Minnesota Vikings', 'time' => '13:00', 'membership' => 'platinum'],
            ],
            'Basketball' => [
                ['home' => 'Chicago Bulls', 'away' => 'Orlando Magic', 'time' => '19:00', 'membership' => 'bronze'],
                ['home' => 'Portland Trail Blazers', 'away' => 'Utah Jazz', 'time' => '22:00', 'membership' => 'bronze'],
                ['home' => 'Cleveland Cavaliers', 'away' => 'Indiana Pacers', 'time' => '19:30', 'membership' => 'bronze'],
                ['home' => 'Sacramento Kings', 'away' => 'Los Angeles Clippers', 'time' => '22:30', 'membership' => 'silver'],
                ['home' => 'Houston Rockets', 'away' => 'Oklahoma City Thunder', 'time' => '20:00', 'membership' => 'gold'],
                ['home' => 'Charlotte Hornets', 'away' => 'Atlanta Hawks', 'time' => '19:00', 'membership' => 'gold'],
            ],
            'Hockey' => [
                ['home' => 'Chicago Blackhawks', 'away' => 'Detroit Red Wings', 'time' => '19:30', 'membership' => 'bronze'],
                ['home' => 'Anaheim Ducks', 'away' => 'San Jose Sharks', 'time' => '22:00', 'membership' => 'bronze'],
                ['home' => 'Nashville Predators', 'away' => 'St. Louis Blues', 'time' => '20:00', 'membership' => 'silver'],
                ['home' => 'Calgary Flames', 'away' => 'Vancouver Canucks', 'time' => '21:00', 'membership' => 'gold'],
            ],
            'Baseball' => [
                ['home' => 'Tampa Bay Rays', 'away' => 'Baltimore Orioles', 'time' => '19:10', 'membership' => 'bronze'],
                ['home' => 'Milwaukee Brewers', 'away' => 'Cincinnati Reds', 'time' => '20:10', 'membership' => 'bronze'],
                ['home' => 'Arizona Diamondbacks', 'away' => 'San Diego Padres', 'time' => '21:40', 'membership' => 'silver'],
                ['home' => 'Seattle Mariners', 'away' => 'Oakland Athletics', 'time' => '22:10', 'membership' => 'gold'],
                ['home' => 'Miami Marlins', 'away' => 'Washington Nationals', 'time' => '18:40', 'membership' => 'bronze'],
            ],
            'Soccer' => [
                ['home' => 'Chelsea FC', 'away' => 'Arsenal FC', 'time' => '15:00', 'membership' => 'bronze'],
                ['home' => 'AC Milan', 'away' => 'Inter Milan', 'time' => '20:45', 'membership' => 'silver'],
                ['home' => 'Atletico Madrid', 'away' => 'Sevilla FC', 'time' => '21:00', 'membership' => 'gold'],
            ],
            'Tennis' => [
                ['home' => 'Carlos Alcaraz', 'away' => 'Jannik Sinner', 'time' => '14:00', 'membership' => 'silver'],
                ['home' => 'Coco Gauff', 'away' => 'Iga Swiatek', 'time' => '16:00', 'membership' => 'gold'],
                ['home' => 'Daniil Medvedev', 'away' => 'Holger Rune', 'time' => '18:00', 'membership' => 'platinum'],
            ],
            'Golf' => [
                ['home' => 'Scottie Scheffler', 'away' => 'PGA Championship', 'time' => '07:00', 'membership' => 'bronze'],
                ['home' => 'Rory McIlroy', 'away' => 'PGA Championship', 'time' => '07:00', 'membership' => 'silver'],
                ['home' => 'Jon Rahm', 'away' => 'PGA Championship', 'time' => '07:00', 'membership' => 'gold'],
            ],
            'Ultimate Fighting Championship' => [
                ['home' => 'UFC 299', 'away' => 'Main Event', 'time' => '22:00', 'membership' => 'gold'],
                ['home' => 'UFC 299', 'away' => 'Co-Main Event', 'time' => '21:30', 'membership' => 'silver'],
            ],
        ];

        // Get admin user for bets
        $adminUser = User::where('email', 'admin@wewingames.com')->first();
        if (! $adminUser) {
            $adminUser = User::first();
        }

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

                // Generate bet details
                $operator = $operators->random();
                $isPositive = rand(0, 1);
                $odds = $isPositive ? rand(100, 250) : rand(-250, -110);

                // Create different bet types based on sport
                $betTypes = $this->getBetTypes($sportName);
                $betType = $betTypes[array_rand($betTypes)];
                $pick = $this->generatePick($sportName, $homeTeam->name, $awayTeam->name, $betType);
                $analysis = $this->generateAnalysis($sportName, $homeTeam->name, $awayTeam->name);

                // Create bet
                Bet::create([
                    'user_id' => $adminUser->id,
                    'sport_id' => $sport->id,
                    'team_one' => $homeTeam->name,
                    'team_one_id' => $homeTeam->id,
                    'team_two' => $awayTeam->name,
                    'team_two_id' => $awayTeam->id,
                    'tips' => $pick,
                    'markets' => $betType,
                    'wager_name' => $analysis,
                    'odds' => $odds,
                    'wager_odds' => $odds,
                    'membership' => $gameData['membership'],
                    'level' => $gameData['membership'],
                    'status' => 'pending',
                    'game_date' => $tomorrow->format('Y-m-d'),
                    'betting_date' => $tomorrow->format('Y-m-d'),
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
            }
        }

        $this->command->info("Tomorrow's bets seeded successfully!");
    }

    private function getBetTypes($sport): array
    {
        $types = [
            'Football' => ['Point Spread', 'Moneyline', 'Over/Under', 'First Half Total', 'Player Prop'],
            'Basketball' => ['Point Spread', 'Moneyline', 'Total Points', 'First Quarter', 'Player Points'],
            'Hockey' => ['Puck Line', 'Moneyline', 'Total Goals', 'Period Betting', 'Player Props'],
            'Baseball' => ['Run Line', 'Moneyline', 'Total Runs', 'First 5 Innings', 'Player Hits'],
            'Soccer' => ['Three Way', 'Double Chance', 'Total Goals', 'Both Teams Score', 'First Goal'],
            'Tennis' => ['Match Winner', 'Set Betting', 'Total Games', 'Set Score', 'Aces'],
            'Golf' => ['Tournament Winner', 'Top 5', 'Top 10', 'Head to Head', 'Round Leader'],
            'Ultimate Fighting Championship' => ['Fight Winner', 'Method of Victory', 'Round Betting', 'Total Rounds', 'Decision'],
        ];

        return $types[$sport] ?? ['Match Winner', 'Total Points', 'Spread'];
    }

    private function generatePick($sport, $home, $away, $betType): string
    {
        switch ($sport) {
            case 'Football':
            case 'Basketball':
                if (strpos($betType, 'Spread') !== false) {
                    $spread = rand(1, 14) * 0.5;

                    return rand(0, 1) ? "{$home} -{$spread}" : "{$away} +{$spread}";
                } elseif (strpos($betType, 'Total') !== false || strpos($betType, 'Over/Under') !== false) {
                    $total = $sport === 'Football' ? rand(38, 58) : rand(200, 240);

                    return rand(0, 1) ? "Over {$total}" : "Under {$total}";
                }

                return rand(0, 1) ? $home : $away;

            case 'Hockey':
                if ($betType === 'Puck Line') {
                    return rand(0, 1) ? "{$home} -1.5" : "{$away} +1.5";
                } elseif ($betType === 'Total Goals') {
                    $total = rand(5, 7) + 0.5;

                    return rand(0, 1) ? "Over {$total}" : "Under {$total}";
                }

                return rand(0, 1) ? $home : $away;

            case 'Baseball':
                if ($betType === 'Run Line') {
                    return rand(0, 1) ? "{$home} -1.5" : "{$away} +1.5";
                } elseif ($betType === 'Total Runs') {
                    $total = rand(7, 11) + 0.5;

                    return rand(0, 1) ? "Over {$total}" : "Under {$total}";
                }

                return rand(0, 1) ? $home : $away;

            case 'Soccer':
                if ($betType === 'Total Goals') {
                    $total = rand(2, 3) + 0.5;

                    return rand(0, 1) ? "Over {$total}" : "Under {$total}";
                } elseif ($betType === 'Both Teams Score') {
                    return rand(0, 1) ? 'Yes' : 'No';
                }

                return rand(0, 1) ? $home : $away;

            case 'Tennis':
                if ($betType === 'Total Games') {
                    $total = rand(20, 24) + 0.5;

                    return rand(0, 1) ? "Over {$total}" : "Under {$total}";
                }

                return rand(0, 1) ? "{$home} in Straight Sets" : "{$away} 2-1";

            case 'Golf':
                $positions = ['Win', 'Top 5', 'Top 10', 'Top 20'];

                return "{$home} to ".$positions[array_rand($positions)];

            case 'Ultimate Fighting Championship':
                if ($betType === 'Method of Victory') {
                    $methods = ['KO/TKO', 'Submission', 'Decision'];

                    return 'Fight ends by '.$methods[array_rand($methods)];
                }

                return $home.' to Win';

            default:
                return rand(0, 1) ? $home : $away;
        }
    }

    private function generateAnalysis($sport, $home, $away): string
    {
        $analyses = [
            "Tomorrow's matchup presents excellent value with recent form analysis.",
            "Key statistics favor this selection for tomorrow's contest.",
            'Momentum and matchup advantages align perfectly here.',
            'Historical performance in similar conditions supports this pick.',
            'Advanced metrics indicate strong potential for tomorrow.',
            'Recent trends and head-to-head data favor this outcome.',
            'Situational factors create a prime betting opportunity.',
            "Form analysis shows clear edge in tomorrow's matchup.",
        ];

        return $analyses[array_rand($analyses)]." {$home} vs {$away}.";
    }

    private function getLeague($sport): string
    {
        $leagues = [
            'Football' => 'NFL',
            'Basketball' => 'NBA',
            'Hockey' => 'NHL',
            'Baseball' => 'MLB',
            'Soccer' => 'Premier League',
            'Tennis' => 'ATP Tour',
            'Golf' => 'PGA Tour',
            'Ultimate Fighting Championship' => 'UFC',
        ];

        return $leagues[$sport] ?? $sport;
    }
}
