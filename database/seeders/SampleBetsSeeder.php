<?php

namespace Database\Seeders;

use App\Models\Bet;
use App\Models\Game;
use App\Models\Operator;
use App\Models\Sport;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleBetsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sports if they don't exist
        $sports = ['Football', 'Basketball', 'Baseball', 'Hockey', 'Soccer', 'Golf', 'UFC'];

        foreach ($sports as $name) {
            Sport::firstOrCreate(['name' => $name]);
        }

        // Create operators if they don't exist
        $operators = ['DraftKings', 'FanDuel', 'BetMGM', 'Caesars', 'PointsBet'];

        foreach ($operators as $name) {
            Operator::firstOrCreate(['name' => $name]);
        }

        // Sample teams data
        $teamsData = [
            'Football' => [
                ['name' => 'Ohio Bobcats', 'sport' => 'Football'],
                ['name' => 'San Diego State Aztecs', 'sport' => 'Football'],
                ['name' => 'Kansas City Chiefs', 'sport' => 'Football'],
                ['name' => 'Buffalo Bills', 'sport' => 'Football'],
                ['name' => 'Philadelphia Eagles', 'sport' => 'Football'],
                ['name' => 'Dallas Cowboys', 'sport' => 'Football'],
            ],
            'Basketball' => [
                ['name' => 'Los Angeles Lakers', 'sport' => 'Basketball'],
                ['name' => 'Boston Celtics', 'sport' => 'Basketball'],
                ['name' => 'Golden State Warriors', 'sport' => 'Basketball'],
                ['name' => 'Miami Heat', 'sport' => 'Basketball'],
                ['name' => 'Denver Nuggets', 'sport' => 'Basketball'],
                ['name' => 'Phoenix Suns', 'sport' => 'Basketball'],
            ],
            'Baseball' => [
                ['name' => 'New York Yankees', 'sport' => 'Baseball'],
                ['name' => 'Los Angeles Dodgers', 'sport' => 'Baseball'],
                ['name' => 'Houston Astros', 'sport' => 'Baseball'],
                ['name' => 'Atlanta Braves', 'sport' => 'Baseball'],
                ['name' => 'Tampa Bay Rays', 'sport' => 'Baseball'],
                ['name' => 'San Diego Padres', 'sport' => 'Baseball'],
            ],
            'Hockey' => [
                ['name' => 'Colorado Avalanche', 'sport' => 'Hockey'],
                ['name' => 'Tampa Bay Lightning', 'sport' => 'Hockey'],
                ['name' => 'Vegas Golden Knights', 'sport' => 'Hockey'],
                ['name' => 'Edmonton Oilers', 'sport' => 'Hockey'],
                ['name' => 'New York Rangers', 'sport' => 'Hockey'],
                ['name' => 'Toronto Maple Leafs', 'sport' => 'Hockey'],
            ],
            'Soccer' => [
                ['name' => 'Manchester United', 'sport' => 'Soccer'],
                ['name' => 'Manchester City', 'sport' => 'Soccer'],
                ['name' => 'Liverpool', 'sport' => 'Soccer'],
                ['name' => 'Chelsea', 'sport' => 'Soccer'],
                ['name' => 'Arsenal', 'sport' => 'Soccer'],
                ['name' => 'Tottenham Hotspur', 'sport' => 'Soccer'],
            ],
        ];

        // Create teams
        $teams = [];
        foreach ($teamsData as $sport => $sportTeams) {
            $sportModel = Sport::where('name', $sport)->first();
            foreach ($sportTeams as $teamData) {
                $team = Team::firstOrCreate(
                    ['name' => $teamData['name']],
                    [
                        'slug' => Str::slug($teamData['name']),
                        'sport_id' => $sportModel->id,
                    ]
                );
                $teams[$sport][] = $team;
            }
        }

        // Create sample bets
        $leagues = [
            'Football' => ['NFL', 'NCAA Football', 'Premier League'],
            'Basketball' => ['NBA', 'NCAA Basketball', 'EuroLeague'],
            'Baseball' => ['MLB', 'Minor League Baseball'],
            'Hockey' => ['NHL', 'AHL', 'KHL'],
            'Soccer' => ['Premier League', 'La Liga', 'Champions League'],
        ];

        $markets = ['Moneyline', 'Spread', 'Over/Under', 'Player Props', 'Team Props'];
        $memberships = ['silver', 'gold', 'platinum'];
        $tips = [
            'Moneyline' => ['Home ML', 'Away ML'],
            'Spread' => ['Home -7.5', 'Away +7.5', 'Home -3.5', 'Away +3.5'],
            'Over/Under' => ['Over 45.5', 'Under 45.5', 'Over 220.5', 'Under 220.5'],
            'Player Props' => ['Player A Over 25.5 Points', 'Player B Under 10.5 Rebounds'],
            'Team Props' => ['Team Total Over 110.5', 'Team Total Under 105.5'],
        ];

        // Create 50 sample bets
        $gameCounter = 1;
        for ($i = 0; $i < 50; $i++) {
            $sport = array_rand($teams);
            $sportModel = Sport::where('name', $sport)->first();
            $sportTeams = $teams[$sport];

            // Pick two random teams
            $teamIndices = array_rand($sportTeams, 2);
            $teamOne = $sportTeams[$teamIndices[0]];
            $teamTwo = $sportTeams[$teamIndices[1]];

            // Create a game
            $gameDate = Carbon::now()->addDays(rand(0, 7))->addHours(rand(12, 22));
            $operator = Operator::inRandomOrder()->first();
            $game = Game::create([
                'title' => 'Game '.$gameCounter++.': '.$teamOne->name.' vs '.$teamTwo->name.' - '.$gameDate->format('M d'),
                'game_date' => $gameDate,
                'sport_id' => $sportModel->id,
                'operator_id' => $operator->id,
                'game_name' => 'scheduled',
                'props' => 'Standard',
                'line' => rand(-10, 10) > 0 ? '+'.rand(1, 10) : '-'.rand(1, 10),
                'wager_team' => rand(0, 1) ? $teamOne->name : $teamTwo->name,
                'post_availablity' => $memberships[array_rand($memberships)],
                'odds' => rand(-150, 150) > 0 ? '+'.rand(100, 300) : '-'.rand(100, 150),
                'type' => $markets[array_rand($markets)],
                'subsection' => $leagues[$sport][array_rand($leagues[$sport])],
                'team1' => $teamOne->name,
                'team2' => $teamTwo->name,
                'team1_img' => null,
                'team2_img' => null,
            ]);

            $market = $markets[array_rand($markets)];
            $operator = Operator::inRandomOrder()->first();

            // Create bet
            Bet::create([
                'sports' => $sport,
                'league' => $game->subsection,
                'matches' => $teamOne->name.' vs '.$teamTwo->name,
                'markets' => $market,
                'team_one' => $teamOne->name,
                'team_two' => $teamTwo->name,
                'tips' => $tips[$market][array_rand($tips[$market])],
                'betting_date' => Carbon::now()->addDays(rand(0, 7))->format('Y-m-d'),
                'wager_odds' => rand(-150, 150) > 0 ? '+'.rand(100, 300) : '-'.rand(100, 150),
                'membership' => $memberships[array_rand($memberships)],
                'wager_amount' => rand(10, 100),
                'winning_amount' => null,
                'profit_amount' => null,
                'roi' => null,
                'status' => 'Pending',
                'referrer' => rand(0, 1) ? $operators[array_rand($operators)] : null,
                'place_fraction' => rand(0, 1) ? rand(1, 10) / 10 : null,
            ]);
        }

        // Create some historical bets with results
        for ($i = 0; $i < 30; $i++) {
            $sport = array_rand($teams);
            $sportModel = Sport::where('name', $sport)->first();
            $sportTeams = $teams[$sport];

            $teamIndices = array_rand($sportTeams, 2);
            $teamOne = $sportTeams[$teamIndices[0]];
            $teamTwo = $sportTeams[$teamIndices[1]];

            $gameDate = Carbon::now()->subDays(rand(1, 30))->addHours(rand(12, 22));
            $operator = Operator::inRandomOrder()->first();
            $game = Game::create([
                'title' => 'Game '.$gameCounter++.': '.$teamOne->name.' vs '.$teamTwo->name.' - '.$gameDate->format('M d'),
                'game_date' => $gameDate,
                'sport_id' => $sportModel->id,
                'operator_id' => $operator->id,
                'game_name' => 'completed',
                'props' => 'Standard',
                'line' => rand(-10, 10) > 0 ? '+'.rand(1, 10) : '-'.rand(1, 10),
                'wager_team' => rand(0, 1) ? $teamOne->name : $teamTwo->name,
                'post_availablity' => $memberships[array_rand($memberships)],
                'odds' => rand(-150, 150) > 0 ? '+'.rand(100, 300) : '-'.rand(100, 150),
                'type' => $markets[array_rand($markets)],
                'subsection' => $leagues[$sport][array_rand($leagues[$sport])],
                'team1' => $teamOne->name,
                'team2' => $teamTwo->name,
                'team1_img' => null,
                'team2_img' => null,
            ]);

            $market = $markets[array_rand($markets)];
            $operator = Operator::inRandomOrder()->first();
            $statuses = ['Won', 'Lost', 'Push'];

            $status = $statuses[array_rand($statuses)];
            $wagerAmount = rand(10, 100);
            $profit = $status === 'Won' ? $wagerAmount * (rand(50, 200) / 100) : ($status === 'Lost' ? -$wagerAmount : 0);

            Bet::create([
                'sports' => $sport,
                'league' => $game->subsection,
                'matches' => $teamOne->name.' vs '.$teamTwo->name,
                'markets' => $market,
                'team_one' => $teamOne->name,
                'team_two' => $teamTwo->name,
                'tips' => $tips[$market][array_rand($tips[$market])],
                'betting_date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d'),
                'wager_odds' => rand(-150, 150) > 0 ? '+'.rand(100, 300) : '-'.rand(100, 150),
                'membership' => $memberships[array_rand($memberships)],
                'wager_amount' => $wagerAmount,
                'winning_amount' => $status === 'Won' ? $wagerAmount + $profit : 0,
                'profit_amount' => $profit,
                'roi' => $profit != 0 ? round(($profit / $wagerAmount) * 100, 2) : 0,
                'status' => $status,
                'referrer' => rand(0, 1) ? $operators[array_rand($operators)] : null,
                'place_fraction' => rand(0, 1) ? rand(1, 10) / 10 : null,
            ]);
        }

        $this->command->info('Sample bets have been seeded successfully!');
    }
}
