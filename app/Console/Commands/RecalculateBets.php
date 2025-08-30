<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Services\BetCalculationService;
use App\Services\BetService;
use Illuminate\Console\Command;

class RecalculateBets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bets:recalculate 
                            {--dry-run : Preview changes without saving}
                            {--id= : Recalculate specific bet ID}
                            {--each-way : Only recalculate Each Way bets}
                            {--with-position : Only recalculate bets with position data}
                            {--year= : Only recalculate bets from specific year}
                            {--batch-size=100 : Number of bets to process at once}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate bet profits and statuses based on position data';

    protected BetCalculationService $calculationService;

    protected BetService $betService;

    /**
     * Create a new command instance.
     */
    public function __construct(BetCalculationService $calculationService, BetService $betService)
    {
        parent::__construct();
        $this->calculationService = $calculationService;
        $this->betService = $betService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        $this->info($isDryRun ? 'Running in DRY RUN mode - no changes will be saved' : 'Starting bet recalculation...');

        // Build query
        $query = Bet::query();

        if ($betId = $this->option('id')) {
            $query->where('id', $betId);
        }

        if ($this->option('each-way')) {
            $query->where('is_each_way', true);
        }

        if ($this->option('with-position')) {
            $query->whereNotNull('finishing_position');
        }

        if ($year = $this->option('year')) {
            $query->whereYear('betting_date', $year);
        }

        $totalBets = $query->count();
        $this->info("Found {$totalBets} bets to process");

        if ($totalBets === 0) {
            $this->info('No bets found matching criteria');

            return 0;
        }

        $processed = 0;
        $updated = 0;
        $errors = 0;
        $changes = [];

        // Process in chunks
        $query->chunk($batchSize, function ($bets) use ($isDryRun, &$processed, &$updated, &$errors, &$changes) {
            foreach ($bets as $bet) {
                $processed++;

                try {
                    $oldValues = [
                        'status' => $bet->status,
                        'winning_amount' => $bet->winning_amount,
                        'profit_amount' => $bet->profit_amount,
                        'bet_result_type' => $bet->bet_result_type,
                    ];

                    // Recalculate based on bet type
                    $newValues = $this->recalculateBet($bet);

                    // Check if values changed
                    $hasChanges = false;
                    foreach ($oldValues as $key => $oldValue) {
                        if (isset($newValues[$key]) && $newValues[$key] != $oldValue) {
                            $hasChanges = true;
                            break;
                        }
                    }

                    if ($hasChanges) {
                        $updated++;

                        $change = [
                            'id' => $bet->id,
                            'sport' => $bet->sport,
                            'wager_name' => $bet->wager_name,
                            'position' => $bet->finishing_position,
                            'old' => $oldValues,
                            'new' => $newValues,
                        ];

                        if (! $isDryRun) {
                            $bet->update($newValues);
                        }

                        // Show first 10 changes
                        if (count($changes) < 10) {
                            $changes[] = $change;
                        }
                    }

                } catch (\Exception $e) {
                    $errors++;
                    $this->error("Error processing bet {$bet->id}: ".$e->getMessage());
                }

                if ($processed % 100 === 0) {
                    $this->info("Processed {$processed}/{$totalBets} bets...");
                }
            }
        });

        // Display summary
        $this->newLine();
        $this->info('=== RECALCULATION SUMMARY ===');
        $this->info("Total bets processed: {$processed}");
        $this->info("Bets requiring updates: {$updated}");
        $this->info("Errors encountered: {$errors}");

        // Show sample changes
        if (! empty($changes)) {
            $this->newLine();
            $this->info('=== SAMPLE CHANGES ===');

            foreach ($changes as $change) {
                $this->newLine();
                $this->info("Bet ID: {$change['id']} - {$change['sport']} - {$change['wager_name']}");
                $this->info("Position: {$change['position']}");

                $this->table(
                    ['Field', 'Old Value', 'New Value'],
                    array_map(function ($key) use ($change) {
                        return [
                            $key,
                            $change['old'][$key] ?? 'null',
                            $change['new'][$key] ?? 'null',
                        ];
                    }, array_keys($change['old']))
                );
            }

            if ($updated > 10) {
                $this->info('... and '.($updated - 10).' more changes');
            }
        }

        if ($isDryRun) {
            $this->newLine();
            $this->warn('DRY RUN COMPLETE - No changes were saved');
            $this->info('Run without --dry-run to apply changes');
        } else {
            $this->newLine();
            $this->info('Recalculation complete!');
        }

        return 0;
    }

    /**
     * Recalculate a single bet based on its type and position data
     */
    protected function recalculateBet(Bet $bet): array
    {
        // For Each Way bets with position data
        if ($bet->is_each_way && $bet->finishing_position && $bet->places_paid) {
            $deadHeatInfo = null;

            if ($bet->is_dead_heat && $bet->dead_heat_players && $bet->dead_heat_spots !== null) {
                $deadHeatInfo = [
                    'players_tied' => $bet->dead_heat_players,
                    'spots_available' => $bet->dead_heat_spots,
                ];
            }

            $calculation = $this->betService->calculateEachWayPayoutWithDeadHeat(
                $bet,
                $bet->finishing_position,
                $bet->places_paid,
                $deadHeatInfo
            );

            return [
                'status' => $calculation['status'],
                'bet_result_type' => $calculation['bet_result_type'],
                'winning_amount' => $calculation['winning_amount'],
                'profit_amount' => $calculation['profit_amount'],
                'place_payout' => $calculation['place_payout'] ?? null,
            ];
        }

        // For Top-X bets
        if ($bet->wager_type && preg_match('/top\s*(\d+)/i', $bet->wager_type, $matches)) {
            $topX = (int) $matches[1];

            if ($bet->finishing_position) {
                $calculation = $this->calculationService->calculateTopXBet($bet, $bet->finishing_position, $topX);

                return [
                    'status' => $calculation['status'],
                    'bet_result_type' => $calculation['bet_result_type'],
                    'winning_amount' => $calculation['winning_amount'],
                    'profit_amount' => $calculation['profit_amount'],
                ];
            }
        }

        // Default: recalculate using existing status
        return $this->calculationService->recalculateBet($bet);
    }
}
