<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\Team;
use App\Models\TeamAlias;
use Illuminate\Console\Command;

class ReportAliasUsage extends Command
{
    protected $signature = 'teams:alias-report 
                            {--export : Export results to CSV file}
                            {--team= : Report for specific team ID}
                            {--sport= : Report for specific sport ID}';

    protected $description = 'Generate a report on team alias usage and effectiveness';

    public function handle()
    {
        $this->info('Team Alias Usage Report');
        $this->info('=======================');

        // Get filters
        $teamId = $this->option('team');
        $sportId = $this->option('sport');

        // Build query
        $query = TeamAlias::with(['team.sport']);

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        if ($sportId) {
            $query->whereHas('team', function ($q) use ($sportId) {
                $q->where('sport_id', $sportId);
            });
        }

        $aliases = $query->get();

        if ($aliases->isEmpty()) {
            $this->warn('No aliases found with the given criteria.');

            return Command::SUCCESS;
        }

        // Collect statistics
        $stats = [];
        $totalMatches = 0;

        foreach ($aliases as $alias) {
            // Count how many times this alias matched in bets
            $matchCount = Bet::where(function ($q) use ($alias) {
                $q->where('team_one', $alias->alias)
                    ->orWhere('team_two', $alias->alias);
            })->count();

            $stats[] = [
                'team_id' => $alias->team_id,
                'team_name' => $alias->team->name,
                'sport' => $alias->team->sport->name,
                'alias' => $alias->alias,
                'matches' => $matchCount,
                'created_at' => $alias->created_at->format('Y-m-d H:i:s'),
            ];

            $totalMatches += $matchCount;
        }

        // Sort by match count descending
        usort($stats, function ($a, $b) {
            return $b['matches'] <=> $a['matches'];
        });

        // Display summary
        $this->info("\nSummary:");
        $this->info('Total aliases: '.count($stats));
        $this->info('Total matches: '.$totalMatches);
        $this->info('Average matches per alias: '.round($totalMatches / count($stats), 2));

        // Display most used aliases
        $this->info("\nTop 10 Most Used Aliases:");
        $this->table(
            ['Team', 'Sport', 'Alias', 'Matches'],
            array_map(function ($stat) {
                return [
                    $stat['team_name'],
                    $stat['sport'],
                    $stat['alias'],
                    $stat['matches'],
                ];
            }, array_slice($stats, 0, 10))
        );

        // Display unused aliases
        $unusedAliases = array_filter($stats, function ($stat) {
            return $stat['matches'] === 0;
        });

        if (count($unusedAliases) > 0) {
            $this->warn("\nUnused Aliases: ".count($unusedAliases));
            if (count($unusedAliases) <= 10) {
                $this->table(
                    ['Team', 'Sport', 'Alias'],
                    array_map(function ($stat) {
                        return [
                            $stat['team_name'],
                            $stat['sport'],
                            $stat['alias'],
                        ];
                    }, $unusedAliases)
                );
            } else {
                $this->info('Showing first 10 unused aliases:');
                $this->table(
                    ['Team', 'Sport', 'Alias'],
                    array_map(function ($stat) {
                        return [
                            $stat['team_name'],
                            $stat['sport'],
                            $stat['alias'],
                        ];
                    }, array_slice($unusedAliases, 0, 10))
                );
            }
        }

        // Group by sport
        $bySport = [];
        foreach ($stats as $stat) {
            $sport = $stat['sport'];
            if (! isset($bySport[$sport])) {
                $bySport[$sport] = [
                    'count' => 0,
                    'matches' => 0,
                ];
            }
            $bySport[$sport]['count']++;
            $bySport[$sport]['matches'] += $stat['matches'];
        }

        $this->info("\nAliases by Sport:");
        $this->table(
            ['Sport', 'Alias Count', 'Total Matches', 'Avg Matches/Alias'],
            array_map(function ($sport, $data) {
                return [
                    $sport,
                    $data['count'],
                    $data['matches'],
                    round($data['matches'] / $data['count'], 2),
                ];
            }, array_keys($bySport), $bySport)
        );

        // Export to CSV if requested
        if ($this->option('export')) {
            $filename = 'alias_usage_report_'.date('Y-m-d_His').'.csv';
            $filepath = storage_path('app/'.$filename);

            $fp = fopen($filepath, 'w');
            fputcsv($fp, ['Team ID', 'Team Name', 'Sport', 'Alias', 'Matches', 'Created At']);

            foreach ($stats as $stat) {
                fputcsv($fp, [
                    $stat['team_id'],
                    $stat['team_name'],
                    $stat['sport'],
                    $stat['alias'],
                    $stat['matches'],
                    $stat['created_at'],
                ]);
            }

            fclose($fp);
            $this->info("\nReport exported to: ".$filepath);
        }

        // Suggestions for optimization
        $this->info("\nOptimization Suggestions:");

        // Find teams with many aliases
        $teamsWithManyAliases = Team::withCount('aliases')
            ->having('aliases_count', '>', 5)
            ->orderBy('aliases_count', 'desc')
            ->limit(5)
            ->get();

        if ($teamsWithManyAliases->isNotEmpty()) {
            $this->warn("\nTeams with many aliases (consider consolidation):");
            foreach ($teamsWithManyAliases as $team) {
                $this->line("  - {$team->name}: {$team->aliases_count} aliases");
            }
        }

        // Find potential duplicate aliases
        $this->checkForSimilarAliases();

        return Command::SUCCESS;
    }

    private function checkForSimilarAliases()
    {
        $aliases = TeamAlias::with('team')->get();
        $similar = [];

        foreach ($aliases as $i => $alias1) {
            foreach ($aliases as $j => $alias2) {
                if ($i >= $j) {
                    continue;
                }
                if ($alias1->team_id === $alias2->team_id) {
                    continue;
                }

                // Check for very similar aliases
                $similarity = similar_text(
                    strtolower($alias1->alias),
                    strtolower($alias2->alias),
                    $percent
                );

                if ($percent > 80) {
                    $similar[] = [
                        'alias1' => $alias1->alias,
                        'team1' => $alias1->team->name,
                        'alias2' => $alias2->alias,
                        'team2' => $alias2->team->name,
                        'similarity' => round($percent, 2),
                    ];
                }
            }
        }

        if (! empty($similar)) {
            // Sort by similarity descending
            usort($similar, function ($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });

            $this->warn("\nPotentially conflicting aliases (similar names for different teams):");
            $this->table(
                ['Alias 1', 'Team 1', 'Alias 2', 'Team 2', 'Similarity %'],
                array_map(function ($item) {
                    return [
                        $item['alias1'],
                        $item['team1'],
                        $item['alias2'],
                        $item['team2'],
                        $item['similarity'].'%',
                    ];
                }, array_slice($similar, 0, 10))
            );
        }
    }
}
