<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\BetTeam;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MapParlayBets extends Command
{
    protected $signature = 'parlays:map {--dry-run : Run without making changes} {--limit=0 : Process only N parlays (0 for all)}';

    protected $description = 'Map parlay bets to their individual teams in the bet_teams table';

    private $stats = [
        'total_parlays' => 0,
        'fully_mapped' => 0,
        'partially_mapped' => 0,
        'unmapped' => 0,
        'teams_extracted' => 0,
        'teams_mapped' => 0,
        'errors' => 0,
    ];

    private $unmappedTeams = [];

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Starting parlay bet mapping process...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        // Get parlay bets
        $query = Bet::where('is_parlay', true)
            ->orWhere('wager_type', 'LIKE', '%parlay%')
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $totalParlays = $query->count();
        $this->info("Found {$totalParlays} parlay bets to process");

        if ($totalParlays === 0) {
            $this->info('No parlay bets found!');

            return Command::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($totalParlays);
        $progressBar->start();

        $query->chunk(50, function ($bets) use ($dryRun, $progressBar) {
            foreach ($bets as $bet) {
                $this->processParlayBet($bet, $dryRun);
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->displayResults();

        return Command::SUCCESS;
    }

    private function processParlayBet(Bet $bet, bool $dryRun): void
    {
        $this->stats['total_parlays']++;

        try {
            // Skip if already has teams in bet_teams
            if (! $dryRun && $bet->parlayTeams()->exists()) {
                $this->stats['fully_mapped']++;

                return;
            }

            // Extract team names from the parlay
            $teamNames = $this->extractTeamsFromParlay($bet);

            if (empty($teamNames)) {
                $this->stats['unmapped']++;

                return;
            }

            $this->stats['teams_extracted'] += count($teamNames);

            // Try to find the sport first
            $sport = null;
            if ($bet->sports) {
                $sport = Sport::where('name', $bet->sports)->first();
            }

            $mappedTeams = [];
            $unmappedCount = 0;

            // Try to map each team
            foreach ($teamNames as $teamName) {
                $team = Team::findByNameOrAlias($teamName, $sport?->id);

                if ($team) {
                    $mappedTeams[] = $team->id;
                    $this->stats['teams_mapped']++;
                } else {
                    $unmappedCount++;
                    $this->trackUnmappedTeam($teamName, $bet->sports);
                }
            }

            // Create bet_team records if not dry run
            if (! $dryRun && ! empty($mappedTeams)) {
                DB::transaction(function () use ($bet, $mappedTeams) {
                    // Mark as parlay
                    $bet->update(['is_parlay' => true]);

                    // Delete existing bet_teams if any
                    $bet->parlayTeams()->delete();

                    // Create new bet_team records
                    foreach ($mappedTeams as $teamId) {
                        BetTeam::create([
                            'bet_id' => $bet->id,
                            'team_id' => $teamId,
                        ]);
                    }
                });
            }

            // Update statistics
            if (count($mappedTeams) === count($teamNames)) {
                $this->stats['fully_mapped']++;
            } elseif (count($mappedTeams) > 0) {
                $this->stats['partially_mapped']++;
            } else {
                $this->stats['unmapped']++;
            }

        } catch (\Exception $e) {
            $this->stats['errors']++;
            $this->error("Error processing parlay bet {$bet->id}: ".$e->getMessage());
        }
    }

    private function extractTeamsFromParlay(Bet $bet): array
    {
        $teams = [];

        // Combine team_one and team_two fields
        $teamText = trim(($bet->team_one ?? '').' '.($bet->team_two ?? ''));

        if (empty($teamText)) {
            // Try to extract from tips or matches fields
            $teamText = $bet->tips ?? $bet->matches ?? '';
        }

        // Remove number prefixes
        $teamText = preg_replace('/^\d+\.\s+/', '', $teamText);

        // Handle "Team1 @ Team2 & Team3 @ Team4" format
        if (Str::contains($teamText, ' & ') && Str::contains($teamText, ' @ ')) {
            $games = explode(' & ', $teamText);
            foreach ($games as $game) {
                if (Str::contains($game, ' @ ')) {
                    $gameParts = explode(' @ ', $game);
                    foreach ($gameParts as $part) {
                        $teamName = $this->cleanTeamName($part);
                        if ($teamName) {
                            $teams[] = $teamName;
                        }
                    }
                }
            }
        }
        // Handle other formats
        else {
            // Try splitting by various delimiters
            $delimiters = [' & ', ' vs ', ' v ', ' VS ', ' V ', ', '];

            foreach ($delimiters as $delimiter) {
                if (Str::contains($teamText, $delimiter)) {
                    $parts = explode($delimiter, $teamText);
                    foreach ($parts as $part) {
                        // Further split by @ if present
                        if (Str::contains($part, ' @ ')) {
                            $subParts = explode(' @ ', $part);
                            foreach ($subParts as $subPart) {
                                $teamName = $this->cleanTeamName($subPart);
                                if ($teamName) {
                                    $teams[] = $teamName;
                                }
                            }
                        } else {
                            $teamName = $this->cleanTeamName($part);
                            if ($teamName) {
                                $teams[] = $teamName;
                            }
                        }
                    }
                    break; // Use first matching delimiter
                }
            }
        }

        return array_unique($teams);
    }

    private function cleanTeamName(string $name): ?string
    {
        // Remove number prefixes
        $name = preg_replace('/^\d+\.\s+/', '', $name);

        // Remove common suffixes and betting terms
        $name = preg_replace('/\s*\(.*?\)/', '', $name); // Remove parentheses
        $name = preg_replace('/\s*(ML|ml|Ml|to win|winner|spread|over|under|[+-]\d+\.?\d*)\s*$/i', '', $name);

        // Remove odds
        $name = preg_replace('/\s*[+-]\d+\.?\d*\s*$/', '', $name);

        $name = trim($name);

        // Skip if too short or just numbers
        if (strlen($name) < 3 || preg_match('/^[+-]?\d+\.?\d*$/', $name)) {
            return null;
        }

        return $name;
    }

    private function trackUnmappedTeam(string $teamName, ?string $sport): void
    {
        $key = $teamName.'|'.($sport ?? 'Unknown');
        if (! isset($this->unmappedTeams[$key])) {
            $this->unmappedTeams[$key] = [
                'team' => $teamName,
                'sport' => $sport ?? 'Unknown',
                'count' => 0,
            ];
        }
        $this->unmappedTeams[$key]['count']++;
    }

    private function displayResults(): void
    {
        $this->info('Parlay Mapping Results:');
        $this->info('======================');

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Parlays Processed', $this->stats['total_parlays'], '100%'],
                ['Fully Mapped (All Teams Found)', $this->stats['fully_mapped'], $this->getPercentage('fully_mapped')],
                ['Partially Mapped (Some Teams Found)', $this->stats['partially_mapped'], $this->getPercentage('partially_mapped')],
                ['Unmapped (No Teams Found)', $this->stats['unmapped'], $this->getPercentage('unmapped')],
                ['Total Teams Extracted', $this->stats['teams_extracted'], '-'],
                ['Total Teams Mapped', $this->stats['teams_mapped'], $this->getTeamMappingRate()],
                ['Errors', $this->stats['errors'], $this->getPercentage('errors')],
            ]
        );

        // Show unmapped teams
        if (! empty($this->unmappedTeams)) {
            $this->newLine();
            $this->warn('Top Unmapped Teams in Parlays:');

            // Sort by count descending
            uasort($this->unmappedTeams, function ($a, $b) {
                return $b['count'] - $a['count'];
            });

            $topUnmapped = array_slice($this->unmappedTeams, 0, 15);

            $tableData = [];
            foreach ($topUnmapped as $data) {
                $tableData[] = [
                    Str::limit($data['team'], 40),
                    $data['sport'],
                    $data['count'],
                ];
            }

            $this->table(['Team Name', 'Sport', 'Occurrences'], $tableData);
        }

        // Summary
        $this->newLine();
        $successRate = $this->stats['total_parlays'] > 0
            ? round(($this->stats['fully_mapped'] / $this->stats['total_parlays']) * 100, 2)
            : 0;

        $this->info("Parlay mapping success rate: {$successRate}%");

        if ($this->stats['teams_extracted'] > 0) {
            $teamMappingRate = round(($this->stats['teams_mapped'] / $this->stats['teams_extracted']) * 100, 2);
            $this->info("Individual team mapping rate: {$teamMappingRate}%");
        }
    }

    private function getPercentage(string $stat): string
    {
        if ($this->stats['total_parlays'] === 0) {
            return '0%';
        }

        return round(($this->stats[$stat] / $this->stats['total_parlays']) * 100, 2).'%';
    }

    private function getTeamMappingRate(): string
    {
        if ($this->stats['teams_extracted'] === 0) {
            return '0%';
        }

        return round(($this->stats['teams_mapped'] / $this->stats['teams_extracted']) * 100, 2).'%';
    }
}
