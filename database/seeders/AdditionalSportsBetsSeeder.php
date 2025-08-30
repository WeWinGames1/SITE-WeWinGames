<?php

namespace Database\Seeders;

use App\Models\Bet;
use App\Models\Operator;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AdditionalSportsBetsSeeder extends Seeder
{
    public function run(): void
    {
        // Get today's date
        $today = Carbon::today();

        // Get operators
        $operators = Operator::all();
        if ($operators->isEmpty()) {
            $operators = collect([
                Operator::create(['name' => 'DraftKings']),
                Operator::create(['name' => 'FanDuel']),
            ]);
        }

        // Additional sports with different bet counts to showcase blurred cards
        $sportsData = [
            // Tennis - only 1 bronze bet (will show blurred card for rest)
            'Tennis' => [
                ['home' => 'Novak Djokovic', 'away' => 'Rafael Nadal', 'time' => '14:00', 'membership' => 'bronze'],
                ['home' => 'Roger Federer', 'away' => 'Andy Murray', 'time' => '16:00', 'membership' => 'silver'],
                ['home' => 'Stefanos Tsitsipas', 'away' => 'Alexander Zverev', 'time' => '18:00', 'membership' => 'gold'],
            ],
            // Boxing - exactly 2 bronze bets (guests see all, free users see blurred after 2)
            'Boxing' => [
                ['home' => 'Tyson Fury', 'away' => 'Deontay Wilder', 'time' => '21:00', 'membership' => 'bronze'],
                ['home' => 'Anthony Joshua', 'away' => 'Oleksandr Usyk', 'time' => '22:00', 'membership' => 'bronze'],
                ['home' => 'Canelo Alvarez', 'away' => 'Gennady Golovkin', 'time' => '23:00', 'membership' => 'silver'],
                ['home' => 'Errol Spence Jr', 'away' => 'Terence Crawford', 'time' => '23:30', 'membership' => 'gold'],
            ],
            // NASCAR - 3 bronze bets (guests see 2 + blurred, free users see 3 + blurred)
            'NASCAR' => [
                ['home' => 'Kyle Larson', 'away' => 'Daytona 500', 'time' => '13:00', 'membership' => 'bronze'],
                ['home' => 'Chase Elliott', 'away' => 'Daytona 500', 'time' => '13:00', 'membership' => 'bronze'],
                ['home' => 'Denny Hamlin', 'away' => 'Daytona 500', 'time' => '13:00', 'membership' => 'bronze'],
                ['home' => 'Kyle Busch', 'away' => 'Daytona 500', 'time' => '13:00', 'membership' => 'silver'],
                ['home' => 'Martin Truex Jr', 'away' => 'Daytona 500', 'time' => '13:00', 'membership' => 'gold'],
            ],
            // Cricket - exactly 4 bronze bets (free users see all, guests see 2 + blurred)
            'Cricket' => [
                ['home' => 'India', 'away' => 'Australia', 'time' => '09:30', 'membership' => 'bronze'],
                ['home' => 'England', 'away' => 'New Zealand', 'time' => '10:00', 'membership' => 'bronze'],
                ['home' => 'Pakistan', 'away' => 'South Africa', 'time' => '14:00', 'membership' => 'bronze'],
                ['home' => 'West Indies', 'away' => 'Sri Lanka', 'time' => '15:00', 'membership' => 'bronze'],
                ['home' => 'Bangladesh', 'away' => 'Afghanistan', 'time' => '16:00', 'membership' => 'gold'],
            ],
            // eSports - 5 bronze bets (both guests and free users see blurred)
            'eSports' => [
                ['home' => 'FaZe Clan', 'away' => 'Natus Vincere', 'time' => '17:00', 'membership' => 'bronze'],
                ['home' => 'Cloud9', 'away' => 'Team Liquid', 'time' => '18:00', 'membership' => 'bronze'],
                ['home' => 'G2 Esports', 'away' => 'Fnatic', 'time' => '19:00', 'membership' => 'bronze'],
                ['home' => 'TSM', 'away' => '100 Thieves', 'time' => '20:00', 'membership' => 'bronze'],
                ['home' => 'Evil Geniuses', 'away' => 'OpTic Gaming', 'time' => '21:00', 'membership' => 'bronze'],
                ['home' => 'Sentinels', 'away' => 'NRG', 'time' => '22:00', 'membership' => 'platinum'],
            ],
            // Formula 1 - no bronze bets (blurred card shows "Unlock Premium")
            'Formula 1' => [
                ['home' => 'Max Verstappen', 'away' => 'Monaco GP', 'time' => '14:00', 'membership' => 'silver'],
                ['home' => 'Lewis Hamilton', 'away' => 'Monaco GP', 'time' => '14:00', 'membership' => 'gold'],
                ['home' => 'Charles Leclerc', 'away' => 'Monaco GP', 'time' => '14:00', 'membership' => 'platinum'],
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
                $pick = $this->generatePick($sportName, $homeTeam->name, $awayTeam->name);
                $description = $this->getDescription($sportName);
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
                    'markets' => $description,
                    'wager_name' => $analysis,
                    'odds' => $odds,
                    'wager_odds' => $odds,
                    'membership' => $gameData['membership'],
                    'level' => $gameData['membership'],
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
            }
        }

        $this->command->info('Additional sports bets seeded successfully!');
    }

    private function generatePick($sport, $home, $away): string
    {
        switch ($sport) {
            case 'Tennis':
                return rand(0, 1) ? "{$home} to Win" : "{$away} in Straight Sets";
            case 'Boxing':
                $methods = ['KO/TKO', 'Decision', 'Points'];

                return "{$home} by ".$methods[array_rand($methods)];
            case 'NASCAR':
                $positions = ['Win', 'Top 3', 'Top 5', 'Top 10'];

                return "{$home} to ".$positions[array_rand($positions)];
            case 'Cricket':
                $types = ['to Win', 'Top Batsman', 'Total Runs Over 300'];

                return "{$home} ".$types[array_rand($types)];
            case 'eSports':
                return rand(0, 1) ? "{$home} Map 1 Winner" : 'Total Maps Over 2.5';
            case 'Formula 1':
                $bets = ['to Win', 'Podium Finish', 'Fastest Lap'];

                return "{$home} ".$bets[array_rand($bets)];
            default:
                return "{$home} to Win";
        }
    }

    private function getDescription($sport): string
    {
        $descriptions = [
            'Tennis' => ['Match Winner', 'Set Betting', 'Total Games'],
            'Boxing' => ['Fight Winner', 'Method of Victory', 'Round Betting'],
            'NASCAR' => ['Race Winner', 'Top 3 Finish', 'Head to Head'],
            'Cricket' => ['Match Winner', 'Top Batsman', 'Total Runs'],
            'eSports' => ['Map Winner', 'Match Winner', 'Total Maps'],
            'Formula 1' => ['Race Winner', 'Podium', 'Qualifying'],
        ];

        return $descriptions[$sport][array_rand($descriptions[$sport])] ?? 'Match Winner';
    }

    private function generateAnalysis($sport, $home, $away): string
    {
        $analyses = [
            "Current form strongly favors this selection in today's matchup.",
            'Head-to-head statistics show clear advantage here.',
            'Recent performance metrics indicate excellent value.',
            'Conditions perfectly align for this betting opportunity.',
            'Historical data supports this high-confidence pick.',
        ];

        return $analyses[array_rand($analyses)]." {$home} vs {$away}.";
    }

    private function getLeague($sport): string
    {
        $leagues = [
            'Tennis' => 'ATP Tour',
            'Boxing' => 'Championship',
            'NASCAR' => 'Cup Series',
            'Cricket' => 'International',
            'eSports' => 'Major League',
            'Formula 1' => 'F1 World Championship',
        ];

        return $leagues[$sport] ?? $sport;
    }
}
