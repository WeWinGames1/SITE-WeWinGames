<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\Team;
use App\Models\Sport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MapBetsToTeams extends Command
{
    protected $signature = 'bets:map-teams {--dry-run : Run without making changes} {--limit=0 : Process only N bets (0 for all)}';
    protected $description = 'Map existing bets to teams by matching team_one and team_two text fields to team records';

    private $stats = [
        'total_bets' => 0,
        'mapped_both' => 0,
        'mapped_team_one_only' => 0,
        'mapped_team_two_only' => 0,
        'unmapped' => 0,
        'parlays' => 0,
        'errors' => 0,
    ];

    private $unmappedTeams = [];

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        
        $this->info('Starting bet to team mapping process...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }
        
        // Get bets that haven't been mapped yet
        $query = Bet::whereNull('team_one_id')
            ->orWhereNull('team_two_id')
            ->where(function($q) {
                $q->whereNotNull('team_one')
                    ->orWhereNotNull('team_two');
            })
            ->orderBy('id');
        
        if ($limit > 0) {
            $query->limit($limit);
        }
        
        $totalBets = $query->count();
        $this->info("Found {$totalBets} bets to process");
        
        if ($totalBets === 0) {
            $this->info('No bets need mapping!');
            return Command::SUCCESS;
        }
        
        $progressBar = $this->output->createProgressBar($totalBets);
        $progressBar->start();
        
        $query->chunk(100, function ($bets) use ($dryRun, $progressBar) {
            foreach ($bets as $bet) {
                $this->processBet($bet, $dryRun);
                $progressBar->advance();
            }
        });
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Display results
        $this->displayResults();
        
        return Command::SUCCESS;
    }
    
    private function processBet(Bet $bet, bool $dryRun): void
    {
        $this->stats['total_bets']++;
        
        try {
            // Check if this is a parlay
            if ($this->isParlayBet($bet)) {
                $this->stats['parlays']++;
                if (!$dryRun) {
                    $bet->update(['is_parlay' => true]);
                }
                return;
            }
            
            $updates = [];
            $mappedCount = 0;
            
            // Try to find the sport first
            $sport = null;
            if ($bet->sports) {
                $sport = Sport::where('name', $bet->sports)->first();
            }
            
            // Map team one
            if ($bet->team_one && !$bet->team_one_id) {
                $teamOne = Team::findByNameOrAlias($bet->team_one, $sport?->id);
                if ($teamOne) {
                    $updates['team_one_id'] = $teamOne->id;
                    $mappedCount++;
                } else {
                    $this->trackUnmappedTeam($bet->team_one, $bet->sports);
                }
            } elseif ($bet->team_one_id) {
                $mappedCount++;
            }
            
            // Map team two
            if ($bet->team_two && !$bet->team_two_id) {
                $teamTwo = Team::findByNameOrAlias($bet->team_two, $sport?->id);
                if ($teamTwo) {
                    $updates['team_two_id'] = $teamTwo->id;
                    $mappedCount++;
                } else {
                    $this->trackUnmappedTeam($bet->team_two, $bet->sports);
                }
            } elseif ($bet->team_two_id) {
                $mappedCount++;
            }
            
            // Update the bet if we found any teams
            if (!empty($updates) && !$dryRun) {
                $bet->update($updates);
            }
            
            // Update statistics
            if ($mappedCount === 2) {
                $this->stats['mapped_both']++;
            } elseif ($mappedCount === 1) {
                if (isset($updates['team_one_id']) || $bet->team_one_id) {
                    $this->stats['mapped_team_one_only']++;
                } else {
                    $this->stats['mapped_team_two_only']++;
                }
            } else {
                $this->stats['unmapped']++;
            }
            
        } catch (\Exception $e) {
            $this->stats['errors']++;
            $this->error("Error processing bet {$bet->id}: " . $e->getMessage());
        }
    }
    
    private function isParlayBet(Bet $bet): bool
    {
        // Check wager_type field
        if ($bet->wager_type && stripos($bet->wager_type, 'parlay') !== false) {
            return true;
        }
        
        // Check for parlay patterns in team names
        if ($bet->team_one || $bet->team_two) {
            $teamText = ($bet->team_one ?? '') . ' ' . ($bet->team_two ?? '');
            
            // Look for multiple teams separated by & or @
            if (preg_match('/(.+)\s*&\s*(.+)\s*@\s*(.+)/', $teamText) || 
                substr_count($teamText, ' & ') > 1 ||
                substr_count($teamText, ' @ ') > 2) {
                return true;
            }
        }
        
        return false;
    }
    
    private function trackUnmappedTeam(string $teamName, ?string $sport): void
    {
        $key = $teamName . '|' . ($sport ?? 'Unknown');
        if (!isset($this->unmappedTeams[$key])) {
            $this->unmappedTeams[$key] = [
                'team' => $teamName,
                'sport' => $sport ?? 'Unknown',
                'count' => 0
            ];
        }
        $this->unmappedTeams[$key]['count']++;
    }
    
    private function displayResults(): void
    {
        $this->info('Mapping Results:');
        $this->info('================');
        
        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Bets Processed', $this->stats['total_bets'], '100%'],
                ['Both Teams Mapped', $this->stats['mapped_both'], $this->getPercentage('mapped_both')],
                ['Only Team One Mapped', $this->stats['mapped_team_one_only'], $this->getPercentage('mapped_team_one_only')],
                ['Only Team Two Mapped', $this->stats['mapped_team_two_only'], $this->getPercentage('mapped_team_two_only')],
                ['No Teams Mapped', $this->stats['unmapped'], $this->getPercentage('unmapped')],
                ['Parlays Detected', $this->stats['parlays'], $this->getPercentage('parlays')],
                ['Errors', $this->stats['errors'], $this->getPercentage('errors')],
            ]
        );
        
        // Show top unmapped teams
        if (!empty($this->unmappedTeams)) {
            $this->newLine();
            $this->warn('Top 20 Unmapped Teams:');
            
            // Sort by count descending
            uasort($this->unmappedTeams, function($a, $b) {
                return $b['count'] - $a['count'];
            });
            
            $topUnmapped = array_slice($this->unmappedTeams, 0, 20);
            
            $tableData = [];
            foreach ($topUnmapped as $data) {
                $tableData[] = [
                    $data['team'],
                    $data['sport'],
                    $data['count']
                ];
            }
            
            $this->table(['Team Name', 'Sport', 'Occurrences'], $tableData);
            
            $this->newLine();
            $this->info('Total unique unmapped teams: ' . count($this->unmappedTeams));
            
            // Suggest creating aliases
            if (count($this->unmappedTeams) > 0) {
                $this->newLine();
                $this->info('💡 Tip: You can create team aliases for these unmapped teams to improve matching.');
                $this->info('   Run: php artisan teams:generate-aliases');
                $this->info('   Or manually add aliases in the admin panel.');
            }
        }
        
        // Summary
        $this->newLine();
        $successRate = $this->stats['total_bets'] > 0 
            ? round(($this->stats['mapped_both'] / $this->stats['total_bets']) * 100, 2)
            : 0;
            
        if ($successRate >= 80) {
            $this->info("✅ Excellent mapping rate: {$successRate}% of bets fully mapped!");
        } elseif ($successRate >= 60) {
            $this->warn("⚠️  Good mapping rate: {$successRate}% of bets fully mapped.");
        } else {
            $this->error("❌ Low mapping rate: {$successRate}% of bets fully mapped.");
            $this->info("   Consider importing more team aliases or checking team name formats.");
        }
        
        // Next steps
        if ($this->stats['parlays'] > 0) {
            $this->newLine();
            $this->info("Found {$this->stats['parlays']} parlay bets. Run the parlay migration command to process these:");
            $this->info("php artisan bets:migrate-parlays");
        }
    }
    
    private function getPercentage(string $stat): string
    {
        if ($this->stats['total_bets'] === 0) {
            return '0%';
        }
        
        return round(($this->stats[$stat] / $this->stats['total_bets']) * 100, 2) . '%';
    }
}