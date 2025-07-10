<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportUnmatchedTeams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bets:report-unmatched
                           {--export : Export to CSV file}
                           {--sport= : Filter by sport name}
                           {--league= : Filter by league name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report bets with unmatched teams that need to be linked';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $export = $this->option('export');
        $sportFilter = $this->option('sport');
        $leagueFilter = $this->option('league');

        $this->info('Analyzing bets for unmatched teams...');

        // Query for bets with team names but no team IDs
        $query = Bet::query()
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->whereNotNull('team_one')
                        ->where('team_one', '!=', '')
                        ->whereNull('team_one_id');
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('team_two')
                        ->where('team_two', '!=', '')
                        ->whereNull('team_two_id');
                });
            });

        if ($sportFilter) {
            $query->where('sports', 'LIKE', "%{$sportFilter}%");
        }

        if ($leagueFilter) {
            $query->where('league', 'LIKE', "%{$leagueFilter}%");
        }

        // Get unmatched team statistics
        $unmatchedTeamOne = clone $query;
        $unmatchedTeamOne = $unmatchedTeamOne
            ->whereNotNull('team_one')
            ->where('team_one', '!=', '')
            ->whereNull('team_one_id')
            ->select('team_one as team_name', 'sports', 'league', DB::raw('COUNT(*) as count'))
            ->groupBy('team_one', 'sports', 'league')
            ->orderBy('count', 'desc');

        $unmatchedTeamTwo = clone $query;
        $unmatchedTeamTwo = $unmatchedTeamTwo
            ->whereNotNull('team_two')
            ->where('team_two', '!=', '')
            ->whereNull('team_two_id')
            ->select('team_two as team_name', 'sports', 'league', DB::raw('COUNT(*) as count'))
            ->groupBy('team_two', 'sports', 'league')
            ->orderBy('count', 'desc');

        // Union the results
        $unmatchedTeams = $unmatchedTeamOne->union($unmatchedTeamTwo)
            ->orderBy('count', 'desc')
            ->get();

        // Group by team name for better reporting
        $teamStats = [];
        foreach ($unmatchedTeams as $team) {
            $key = $team->team_name;
            if (!isset($teamStats[$key])) {
                $teamStats[$key] = [
                    'team_name' => $team->team_name,
                    'sports' => [],
                    'leagues' => [],
                    'total_count' => 0,
                ];
            }
            
            if ($team->sports && !in_array($team->sports, $teamStats[$key]['sports'])) {
                $teamStats[$key]['sports'][] = $team->sports;
            }
            
            if ($team->league && !in_array($team->league, $teamStats[$key]['leagues'])) {
                $teamStats[$key]['leagues'][] = $team->league;
            }
            
            $teamStats[$key]['total_count'] += $team->count;
        }

        // Sort by total count
        uasort($teamStats, function ($a, $b) {
            return $b['total_count'] - $a['total_count'];
        });

        // Get summary statistics
        $totalUnmatchedBets = Bet::where(function ($q) {
            $q->where(function ($q1) {
                $q1->whereNotNull('team_one')
                    ->where('team_one', '!=', '')
                    ->whereNull('team_one_id');
            })->orWhere(function ($q2) {
                $q2->whereNotNull('team_two')
                    ->where('team_two', '!=', '')
                    ->whereNull('team_two_id');
            });
        })->count();

        $totalBets = Bet::count();
        $percentUnmatched = $totalBets > 0 ? round(($totalUnmatchedBets / $totalBets) * 100, 2) : 0;

        // Display summary
        $this->info("\n=== SUMMARY ===");
        $this->line("Total Bets: {$totalBets}");
        $this->line("Unmatched Bets: {$totalUnmatchedBets} ({$percentUnmatched}%)");
        $this->line("Unique Unmatched Teams: " . count($teamStats));

        if ($export) {
            $filename = 'unmatched_teams_' . date('Y-m-d_His') . '.csv';
            $handle = fopen($filename, 'w');
            
            // Write headers
            fputcsv($handle, ['Team Name', 'Sports', 'Leagues', 'Bet Count']);
            
            // Write data
            foreach ($teamStats as $stat) {
                fputcsv($handle, [
                    $stat['team_name'],
                    implode(', ', $stat['sports']),
                    implode(', ', $stat['leagues']),
                    $stat['total_count'],
                ]);
            }
            
            fclose($handle);
            $this->info("\nExported to: {$filename}");
        } else {
            // Display top unmatched teams
            $this->info("\n=== TOP 20 UNMATCHED TEAMS ===");
            $this->table(
                ['Team Name', 'Sports', 'Leagues', 'Bet Count'],
                array_map(function ($stat) {
                    return [
                        substr($stat['team_name'], 0, 40),
                        implode(', ', array_slice($stat['sports'], 0, 2)),
                        implode(', ', array_slice($stat['leagues'], 0, 2)),
                        $stat['total_count'],
                    ];
                }, array_slice($teamStats, 0, 20))
            );

            if (count($teamStats) > 20) {
                $this->info("\n... and " . (count($teamStats) - 20) . " more unmatched teams.");
                $this->info("Use --export option to get the full list.");
            }
        }

        // Suggest creating aliases
        $this->info("\n=== SUGGESTED ACTIONS ===");
        $this->line("1. Run 'php artisan bets:extract-teams' to create teams from bet data");
        $this->line("2. Create aliases for common variations (e.g., 'LA Lakers' -> 'Lakers')");
        $this->line("3. Use the admin panel to manually map teams");

        return Command::SUCCESS;
    }
}