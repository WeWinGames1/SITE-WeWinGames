<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\League;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeTeamIssues extends Command
{
    protected $signature = 'teams:analyze-issues {--fix : Fix the issues found}';

    protected $description = 'Analyze team duplicates and missing league assignments';

    public function handle()
    {
        $this->info('Analyzing Team Issues...');
        $this->newLine();

        // 1. Check for duplicate team names
        $this->analyzeDuplicates();

        // 2. Check teams without leagues
        $this->analyzeLeaguelessTeams();

        // 3. Check bet mapping status
        $this->analyzeBetMappings();

        if ($this->option('fix')) {
            if ($this->confirm('Do you want to fix the issues found?')) {
                $this->fixIssues();
            }
        }

        return Command::SUCCESS;
    }

    private function analyzeDuplicates()
    {
        $this->info('1. Analyzing Duplicate Team Names');
        $this->info('==================================');

        $duplicates = Team::select('name', 'sport_id', DB::raw('COUNT(*) as count'))
            ->groupBy('name', 'sport_id')
            ->having('count', '>', 1)
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('✅ No duplicate team names found within the same sport.');
        } else {
            $this->warn('Found duplicate team names:');
            $tableData = [];
            foreach ($duplicates as $dup) {
                $sport = Sport::find($dup->sport_id);
                $tableData[] = [
                    $dup->name,
                    $sport ? $sport->name : 'N/A',
                    $dup->count,
                ];
            }
            $this->table(['Team Name', 'Sport', 'Count'], $tableData);
        }

        $this->newLine();
    }

    private function analyzeLeaguelessTeams()
    {
        $this->info('2. Teams Without League Assignments');
        $this->info('====================================');

        $totalTeams = Team::count();
        $teamsWithoutLeague = Team::whereNull('league_id')->count();

        $this->line('Total teams: '.number_format($totalTeams));
        $this->line('Teams without league: '.number_format($teamsWithoutLeague));
        $this->line('Percentage without league: '.round(($teamsWithoutLeague / $totalTeams) * 100, 2).'%');

        // Show breakdown by sport
        $bySport = Team::whereNull('league_id')
            ->select('sport_id', DB::raw('COUNT(*) as count'))
            ->groupBy('sport_id')
            ->get();

        $this->newLine();
        $this->info('Teams without league by sport:');
        $tableData = [];
        foreach ($bySport as $item) {
            $sport = Sport::find($item->sport_id);
            $tableData[] = [
                $sport ? $sport->name : 'Unknown',
                number_format($item->count),
            ];
        }
        $this->table(['Sport', 'Count'], $tableData);

        $this->newLine();
    }

    private function analyzeBetMappings()
    {
        $this->info('3. Bet Team Mapping Analysis');
        $this->info('=============================');

        // Check bets where team names don't match the mapped team
        $mismatches = Bet::whereNotNull('team_one_id')
            ->with(['teamOne'])
            ->limit(10)
            ->get()
            ->filter(function ($bet) {
                return $bet->teamOne &&
                       strtolower($bet->team_one) !== strtolower($bet->teamOne->name);
            });

        if ($mismatches->isNotEmpty()) {
            $this->warn('Sample of team name mismatches:');
            $tableData = [];
            foreach ($mismatches as $bet) {
                $tableData[] = [
                    $bet->id,
                    substr($bet->team_one, 0, 30),
                    substr($bet->teamOne->name, 0, 30),
                    $bet->sports,
                ];
            }
            $this->table(['Bet ID', 'Text Name', 'Mapped Team', 'Sport'], $tableData);
        }

        // Show unmapped stats
        $unmappedTeamOne = Bet::whereNull('team_one_id')
            ->whereNotNull('team_one')
            ->where('team_one', '!=', '')
            ->count();

        $unmappedTeamTwo = Bet::whereNull('team_two_id')
            ->whereNotNull('team_two')
            ->where('team_two', '!=', '')
            ->count();

        $this->newLine();
        $this->line('Bets with unmapped team_one: '.number_format($unmappedTeamOne));
        $this->line('Bets with unmapped team_two: '.number_format($unmappedTeamTwo));

        $this->newLine();
    }

    private function fixIssues()
    {
        $this->info('Fixing Issues...');
        $this->newLine();

        // 1. Merge duplicate teams
        $this->fixDuplicateTeams();

        // 2. Try to assign leagues based on team names and sports
        $this->assignLeaguesToTeams();
    }

    private function fixDuplicateTeams()
    {
        $this->info('Merging duplicate teams...');

        $duplicates = Team::select('name', 'sport_id', DB::raw('MIN(id) as keep_id'), DB::raw('GROUP_CONCAT(id) as all_ids'))
            ->groupBy('name', 'sport_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        $mergedCount = 0;

        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup->all_ids);
            $keepId = $dup->keep_id;
            $mergeIds = array_diff($ids, [$keepId]);

            // Update all references to point to the kept team
            foreach ($mergeIds as $mergeId) {
                // Update bets
                Bet::where('team_one_id', $mergeId)->update(['team_one_id' => $keepId]);
                Bet::where('team_two_id', $mergeId)->update(['team_two_id' => $keepId]);

                // Update bet_teams (parlays)
                DB::table('bet_teams')->where('team_id', $mergeId)->update(['team_id' => $keepId]);

                // Delete the duplicate
                Team::find($mergeId)->delete();
                $mergedCount++;
            }
        }

        $this->info("✅ Merged {$mergedCount} duplicate teams");
    }

    private function assignLeaguesToTeams()
    {
        $this->info('Assigning leagues to teams...');

        // Common league patterns
        $leaguePatterns = [
            'NHL' => ['Hockey', ['NHL', 'National Hockey League']],
            'NBA' => ['Basketball', ['NBA', 'National Basketball Association']],
            'NFL' => ['Football', ['NFL', 'National Football League']],
            'MLB' => ['Baseball', ['MLB', 'Major League Baseball']],
            'MLS' => ['Soccer', ['MLS', 'Major League Soccer']],
            'Premier League' => ['Soccer', ['Premier League', 'EPL']],
            'PGA' => ['Golf', ['PGA', 'PGA Tour']],
            'UFC' => ['Combat sports', ['UFC', 'Ultimate Fighting']],
        ];

        $assignedCount = 0;

        foreach ($leaguePatterns as $leagueName => $data) {
            [$sportName, $patterns] = $data;

            // Find or create the sport
            $sport = Sport::where('name', $sportName)->first();
            if (! $sport) {
                continue;
            }

            // Find or create the league
            $league = League::where('name', $leagueName)
                ->where('sport_id', $sport->id)
                ->first();

            if (! $league) {
                $league = League::create([
                    'name' => $leagueName,
                    'sport_id' => $sport->id,
                    'abbreviation' => $leagueName,
                    'is_active' => true,
                ]);
            }

            // Check bets for this league pattern
            foreach ($patterns as $pattern) {
                $betTeamIds = Bet::where('sports', $sport->name)
                    ->where('league', 'LIKE', "%{$pattern}%")
                    ->whereNotNull('team_one_id')
                    ->pluck('team_one_id')
                    ->unique();

                $updated = Team::whereIn('id', $betTeamIds)
                    ->whereNull('league_id')
                    ->update(['league_id' => $league->id]);

                $assignedCount += $updated;
            }
        }

        $this->info("✅ Assigned leagues to {$assignedCount} teams");
    }
}
