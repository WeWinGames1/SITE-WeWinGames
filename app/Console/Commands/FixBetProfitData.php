<?php

namespace App\Console\Commands;

use App\Models\Bet;
use Illuminate\Console\Command;

class FixBetProfitData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bets:fix-profit-data {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix profit and winning amount data for pending and settled bets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Running in DRY RUN mode - no changes will be made');
        }

        $this->info('Fixing bet profit data...');

        $fixed = [
            'loss' => 0,
            'void' => 0,
            'won_negative' => 0,
        ];

        // Note: Pending bets are intentionally left with hypothetical values for display purposes
        // Note: Push bets are skipped as they may have odd profit amounts in special cases
        // The BetService already excludes pending bets from all profit calculations

        // 1. Fix loss bets - should have profit_amount = -wager_amount and winning_amount = 0
        $this->info('Fixing loss bets...');
        $lossBets = Bet::where('status', 'loss')
            ->where(function ($query) {
                $query->whereRaw('profit_amount != -wager_amount')
                    ->orWhere('winning_amount', '!=', 0);
            })
            ->get();

        foreach ($lossBets as $bet) {
            $correctProfit = -abs($bet->wager_amount);
            $correctROI = $bet->wager_amount > 0 ? ($correctProfit / $bet->wager_amount) * 100 : -100;
            $this->line("Bet #{$bet->id}: Fixing loss (profit: {$bet->profit_amount} → {$correctProfit}, winning: {$bet->winning_amount} → 0, ROI: {$bet->roi} → {$correctROI}%)");

            if (!$dryRun) {
                $bet->update([
                    'profit_amount' => $correctProfit,
                    'winning_amount' => 0,
                    'roi' => $correctROI,
                ]);
            }

            $fixed['loss']++;
        }

        // 2. Fix void bets - should have profit_amount = 0 and winning_amount = wager_amount
        $this->info('Fixing void bets...');
        $voidBets = Bet::where('status', 'void')
            ->where(function ($query) {
                $query->where('profit_amount', '!=', 0)
                    ->orWhereRaw('winning_amount != wager_amount');
            })
            ->get();

        foreach ($voidBets as $bet) {
            $this->line("Bet #{$bet->id}: Fixing void (profit: {$bet->profit_amount} → 0, winning: {$bet->winning_amount} → {$bet->wager_amount}, ROI: {$bet->roi} → 0%)");

            if (!$dryRun) {
                $bet->update([
                    'profit_amount' => 0,
                    'winning_amount' => $bet->wager_amount,
                    'roi' => 0,
                ]);
            }

            $fixed['void']++;
        }

        // 3. Fix won bets with negative profit or incorrect values
        $this->info('Checking won bets with negative profit...');
        $wonNegativeBets = Bet::whereIn('status', ['won', 'placed'])
            ->where('profit_amount', '<', 0)
            ->get();

        // First, display all the won bets that will be fixed
        if ($wonNegativeBets->count() > 0) {
            $this->newLine();
            $this->warn("Found {$wonNegativeBets->count()} won bets with negative profit:");
            $this->table(
                ['Bet ID', 'Status', 'Wager', 'Winning Amount', 'Profit', 'ROI', 'Issue'],
                $wonNegativeBets->map(function ($bet) {
                    $issue = ($bet->winning_amount < $bet->wager_amount)
                        ? 'winning_amount appears to be profit'
                        : 'negative profit';
                    return [
                        $bet->id,
                        $bet->status,
                        '$' . number_format($bet->wager_amount, 2),
                        '$' . number_format($bet->winning_amount, 2),
                        '$' . number_format($bet->profit_amount, 2),
                        $bet->roi ? round($bet->roi, 2) . '%' : 'null',
                        $issue,
                    ];
                })->toArray()
            );
            $this->newLine();
        }

        foreach ($wonNegativeBets as $bet) {
            // Check if winning_amount might actually be the PROFIT (not total return)
            // If winning_amount < wager_amount, it's likely the profit stored in wrong field
            if ($bet->winning_amount < $bet->wager_amount) {
                // winning_amount appears to be the profit, not the total
                $actualProfit = $bet->winning_amount;
                $actualWinning = $bet->wager_amount + $actualProfit;
                $correctROI = $bet->wager_amount > 0 ? ($actualProfit / $bet->wager_amount) * 100 : 0;

                $this->warn("Bet #{$bet->id}: winning_amount appears to be profit! (wager: {$bet->wager_amount}, winning: {$bet->winning_amount} → {$actualWinning}, profit: {$bet->profit_amount} → {$actualProfit}, ROI: {$bet->roi} → {$correctROI}%)");

                if (!$dryRun) {
                    $bet->update([
                        'profit_amount' => $actualProfit,
                        'winning_amount' => $actualWinning,
                        'roi' => $correctROI,
                    ]);
                }
            } else {
                // Recalculate based on current values or odds
                // If profit_amount is negative but winning_amount makes sense, use winning_amount
                $correctProfit = $bet->winning_amount - $bet->wager_amount;
                $correctROI = $bet->wager_amount > 0 ? ($correctProfit / $bet->wager_amount) * 100 : 0;

                $this->warn("Bet #{$bet->id}: Recalculating profit from winning amount (wager: {$bet->wager_amount}, winning: {$bet->winning_amount}, profit: {$bet->profit_amount} → {$correctProfit}, ROI: {$bet->roi} → {$correctROI}%)");

                if (!$dryRun) {
                    $bet->update([
                        'profit_amount' => $correctProfit,
                        'roi' => $correctROI,
                    ]);
                }
            }

            $fixed['won_negative']++;
        }

        // Summary
        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Type', 'Count'],
            [
                ['Loss bets fixed', $fixed['loss']],
                ['Void bets fixed', $fixed['void']],
                ['Won bets with incorrect values fixed', $fixed['won_negative']],
                ['Total', array_sum($fixed)],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN - No changes were made. Run without --dry-run to apply fixes.');
        } else {
            $this->info('All fixes applied successfully!');
        }

        return 0;
    }

    /**
     * Calculate winning amount based on American odds
     */
    private function calculateWinningAmount(float $stake, $odds): float
    {
        if (!$odds) {
            return $stake; // If no odds, assume push
        }

        $numericOdds = is_numeric($odds) ? $odds : $this->parseOdds($odds);

        if ($numericOdds > 0) {
            // Positive odds (e.g., +150)
            return $stake + ($stake * ($numericOdds / 100));
        } else {
            // Negative odds (e.g., -150)
            return $stake + ($stake * (100 / abs($numericOdds)));
        }
    }

    /**
     * Parse odds from various formats
     */
    private function parseOdds($odds): float
    {
        // Remove any non-numeric characters except +, -, .
        $cleaned = preg_replace('/[^0-9+\-.]/', '', $odds);
        return floatval($cleaned);
    }
}
