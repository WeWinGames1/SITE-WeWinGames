<?php

namespace Database\Seeders;

use App\Models\Bet;
use App\Models\Operator;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PremiumBetsSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();

        // Get operators
        $operators = Operator::all();
        if ($operators->isEmpty()) {
            $operators = collect([
                Operator::create(['name' => 'DraftKings']),
                Operator::create(['name' => 'FanDuel']),
            ]);
        }

        // Get admin user
        $adminUser = User::where('email', 'admin@wewingames.com')->first() ?? User::first();

        // Premium picks for today with detailed analysis
        $premiumPicks = [
            [
                'sport' => 'Football',
                'home' => 'Buffalo Bills',
                'away' => 'Kansas City Chiefs',
                'pick' => 'Buffalo Bills +2.5',
                'type' => 'Point Spread',
                'odds' => -110,
                'membership' => 'platinum',
                'analysis' => '🏆 PLATINUM LOCK: Bills have covered in 8 of their last 10 home games. Josh Allen is 12-3 ATS as a home underdog. Chiefs are 2-7 ATS in their last 9 road games. This line is a gift!',
            ],
            [
                'sport' => 'Basketball',
                'home' => 'Boston Celtics',
                'away' => 'Los Angeles Lakers',
                'pick' => 'Over 228.5',
                'type' => 'Total Points',
                'odds' => -105,
                'membership' => 'gold',
                'analysis' => '⭐ GOLD PICK: Both teams averaging 120+ PPG in last 5. Pace metrics suggest 240+ total likely. Public on the under = value on over.',
            ],
            [
                'sport' => 'Football',
                'home' => 'San Francisco 49ers',
                'away' => 'Dallas Cowboys',
                'pick' => 'Christian McCaffrey Over 87.5 Rushing Yards',
                'type' => 'Player Prop',
                'odds' => -115,
                'membership' => 'platinum',
                'analysis' => '💎 BEST BET: McCaffrey averaging 112 yards vs Dallas defense type. Cowboys allow 5.2 YPC to elite backs. Weather perfect for ground game.',
            ],
            [
                'sport' => 'Hockey',
                'home' => 'Colorado Avalanche',
                'away' => 'Edmonton Oilers',
                'pick' => 'Colorado Avalanche -1.5',
                'type' => 'Puck Line',
                'odds' => +145,
                'membership' => 'gold',
                'analysis' => '🔥 HIGH VALUE: Avs are 15-3 at home vs Edmonton. Oilers on 2nd night of back-to-back. MacKinnon owns this matchup.',
            ],
            [
                'sport' => 'Basketball',
                'home' => 'Milwaukee Bucks',
                'away' => 'Philadelphia 76ers',
                'pick' => 'Giannis Antetokounmpo Over 31.5 Points',
                'type' => 'Player Prop',
                'odds' => -120,
                'membership' => 'silver',
                'analysis' => '📈 Giannis averaging 35.8 PPG in last 8 vs Philly. Sixers frontcourt depleted. Clear path to 35+.',
            ],
            [
                'sport' => 'Baseball',
                'home' => 'Los Angeles Dodgers',
                'away' => 'San Diego Padres',
                'pick' => 'First 5 Innings Over 4.5',
                'type' => 'Total Runs F5',
                'odds' => -125,
                'membership' => 'gold',
                'analysis' => '⚾ SHARP PLAY: Both starters struggling recently. Wind blowing out at 15+ MPH. Early runs expected.',
            ],
            [
                'sport' => 'Soccer',
                'home' => 'Manchester City',
                'away' => 'Liverpool',
                'pick' => 'Both Teams to Score - Yes',
                'type' => 'BTTS',
                'odds' => -140,
                'membership' => 'bronze',
                'analysis' => 'Last 6 H2H meetings have seen both teams score. High-powered offenses on display.',
            ],
            [
                'sport' => 'Golf',
                'home' => 'PGA Tour',
                'away' => 'The Players Championship',
                'pick' => 'Scottie Scheffler Top 5 Finish',
                'type' => 'Tournament Prop',
                'odds' => -150,
                'membership' => 'platinum',
                'analysis' => '🏌️ LOCK: Scheffler has 11 straight top-10s. Course fits his game perfectly. In elite form.',
            ],
            [
                'sport' => 'Football',
                'home' => 'Baltimore Ravens',
                'away' => 'Cincinnati Bengals',
                'pick' => 'Lamar Jackson Over 52.5 Rushing Yards',
                'type' => 'Player Prop',
                'odds' => -110,
                'membership' => 'silver',
                'analysis' => 'Lamar averages 78 rush yards vs CIN. Bengals LB corps banged up. Easy over.',
            ],
            [
                'sport' => 'Basketball',
                'home' => 'Phoenix Suns',
                'away' => 'Denver Nuggets',
                'pick' => 'Nikola Jokic Triple-Double',
                'type' => 'Player Prop Special',
                'odds' => +225,
                'membership' => 'gold',
                'analysis' => '💰 VALUE PLAY: Jokic has 4 triple-doubles in last 6 vs Suns. Great odds for likely outcome.',
            ],
        ];

        foreach ($premiumPicks as $pickData) {
            $sport = Sport::firstOrCreate(['name' => $pickData['sport']]);

            $homeTeam = Team::firstOrCreate(
                ['name' => $pickData['home']],
                ['sport_id' => $sport->id]
            );
            $awayTeam = Team::firstOrCreate(
                ['name' => $pickData['away']],
                ['sport_id' => $sport->id]
            );

            Bet::create([
                'user_id' => $adminUser->id,
                'sport_id' => $sport->id,
                'team_one' => $homeTeam->name,
                'team_one_id' => $homeTeam->id,
                'team_two' => $awayTeam->name,
                'team_two_id' => $awayTeam->id,
                'tips' => $pickData['pick'],
                'markets' => $pickData['type'],
                'wager_name' => $pickData['analysis'],
                'odds' => $pickData['odds'],
                'wager_odds' => $pickData['odds'],
                'membership' => $pickData['membership'],
                'level' => $pickData['membership'],
                'status' => 'pending',
                'game_date' => $today->format('Y-m-d'),
                'betting_date' => $today->format('Y-m-d'),
                'wager_amount' => $pickData['membership'] === 'platinum' ? 200 : 100,
                'winning_amount' => 0,
                'profit_amount' => 0,
                'sports' => $pickData['sport'],
                'sport' => $pickData['sport'],
                'league' => $this->getLeague($pickData['sport']),
                'game' => "{$homeTeam->name} vs {$awayTeam->name}",
                'matches' => "{$homeTeam->name} vs {$awayTeam->name}",
                'referrer' => $operators->random()->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Premium bets with detailed analysis seeded successfully!');
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
