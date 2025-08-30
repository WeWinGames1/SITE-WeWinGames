<?php

namespace App\Services;

use App\Models\Bet;
use Illuminate\Support\Facades\Log;

class BetCalculationService
{
    /**
     * Handle Top-X bet calculations (e.g., Top 40, Top 20)
     */
    public function calculateTopXBet(Bet $bet, string $position, int $topX): array
    {
        $numericPosition = $this->parsePosition($position);

        $result = [
            'status' => 'loss',
            'bet_result_type' => 'loss',
            'winning_amount' => 0,
            'profit_amount' => -$bet->wager_amount,
            'finishing_position' => $position,
            'position_numeric' => $numericPosition,
        ];

        // Check if position qualifies
        if ($numericPosition !== null && $numericPosition <= $topX) {
            // Win - calculate payout
            $winAmount = $this->calculateAmericanOddsPayout($bet->wager_amount, $bet->odds);

            $result['status'] = 'won';
            $result['bet_result_type'] = 'won_outright';
            $result['winning_amount'] = $winAmount;
            $result['profit_amount'] = $winAmount - $bet->wager_amount;
        }

        return $result;
    }

    /**
     * Detect if a position indicates a dead heat
     */
    public function detectDeadHeat(string $position): bool
    {
        // Positions starting with 'T' typically indicate ties
        return str_starts_with(strtoupper(trim($position)), 'T') && preg_match('/^T\d+$/i', trim($position));
    }

    /**
     * Calculate dead heat spots based on position and places paid
     * For example: T7 with 8 places paid and 2 players tied = 1 spot available
     */
    public function calculateDeadHeatSpots(string $position, int $placesPaid, int $playersTied): float
    {
        $numericPosition = $this->parsePosition($position);

        if ($numericPosition === null || $numericPosition > $placesPaid) {
            return 0; // Position doesn't qualify for payment
        }

        // Calculate remaining spots from the tied position to the last paying place
        $spotsFromPosition = $placesPaid - $numericPosition + 1;

        // The available spots are the minimum of spots from position and actual remaining spots
        return min($spotsFromPosition, $playersTied);
    }

    /**
     * Validate Each Way bet data completeness
     */
    public function validateEachWayBetData(array $data): array
    {
        $errors = [];

        if (! isset($data['is_each_way']) || ! $data['is_each_way']) {
            return []; // Not an Each Way bet, no validation needed
        }

        // For settled Each Way bets, we need position and places paid
        if (isset($data['status']) && ! in_array($data['status'], ['pending', 'open'])) {
            if (empty($data['finishing_position'])) {
                $errors[] = 'Finishing position is required for settled Each Way bets';
            }

            if (empty($data['places_paid'])) {
                $errors[] = 'Number of places paid is required for settled Each Way bets';
            }
        }

        // Validate place fraction
        if (isset($data['place_fraction']) && ($data['place_fraction'] <= 0 || $data['place_fraction'] > 1)) {
            $errors[] = 'Place fraction must be between 0 and 1';
        }

        return $errors;
    }

    /**
     * Recalculate all amounts for a bet based on current data
     */
    public function recalculateBet(Bet $bet): array
    {
        // For Each Way bets with position data
        if ($bet->is_each_way && $bet->finishing_position && $bet->places_paid) {
            $service = app(BetService::class);

            $deadHeatInfo = null;
            if ($bet->is_dead_heat && $bet->dead_heat_players && $bet->dead_heat_spots !== null) {
                $deadHeatInfo = [
                    'players_tied' => $bet->dead_heat_players,
                    'spots_available' => $bet->dead_heat_spots,
                ];
            }

            return $service->calculateEachWayPayoutWithDeadHeat(
                $bet,
                $bet->finishing_position,
                $bet->places_paid,
                $deadHeatInfo
            );
        }

        // For Top-X bets
        if ($this->isTopXBet($bet->wager_type)) {
            $topX = $this->extractTopXNumber($bet->wager_type);
            if ($topX && $bet->finishing_position) {
                return $this->calculateTopXBet($bet, $bet->finishing_position, $topX);
            }
        }

        // Default calculation for standard bets
        return $this->calculateStandardBet($bet);
    }

    /**
     * Check if a wager type is a Top-X bet
     */
    private function isTopXBet(?string $wagerType): bool
    {
        if (! $wagerType) {
            return false;
        }

        return preg_match('/top\s*\d+/i', $wagerType);
    }

    /**
     * Extract the number from a Top-X wager type
     */
    private function extractTopXNumber(string $wagerType): ?int
    {
        if (preg_match('/top\s*(\d+)/i', $wagerType, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Calculate standard bet (not Each Way, not Top-X)
     */
    private function calculateStandardBet(Bet $bet): array
    {
        $result = [
            'status' => $bet->status,
            'winning_amount' => 0,
            'profit_amount' => 0,
        ];

        switch ($bet->status) {
            case 'won':
                $result['winning_amount'] = $this->calculateAmericanOddsPayout($bet->wager_amount, $bet->odds);
                $result['profit_amount'] = $result['winning_amount'] - $bet->wager_amount;
                break;

            case 'loss':
                $result['winning_amount'] = 0;
                $result['profit_amount'] = -$bet->wager_amount;
                break;

            case 'push':
            case 'void':
                $result['winning_amount'] = $bet->wager_amount;
                $result['profit_amount'] = 0;
                break;
        }

        return $result;
    }

    /**
     * Parse position string to numeric value
     */
    private function parsePosition(string $position): ?int
    {
        // Use the same logic as BetService
        $service = app(BetService::class);

        return $service->parsePosition($position);
    }

    /**
     * Calculate payout for American odds
     */
    private function calculateAmericanOddsPayout($stake, $odds): float
    {
        if ($odds > 0) {
            return $stake * ($odds / 100) + $stake;
        } else {
            return $stake * (100 / abs($odds)) + $stake;
        }
    }

    /**
     * Batch recalculate multiple bets
     */
    public function batchRecalculate(array $betIds): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($betIds as $betId) {
            try {
                $bet = Bet::find($betId);
                if (! $bet) {
                    $results['failed']++;
                    $results['errors'][] = "Bet ID {$betId} not found";

                    continue;
                }

                $calculation = $this->recalculateBet($bet);

                // Update the bet with new calculations
                $bet->update([
                    'winning_amount' => $calculation['winning_amount'] ?? 0,
                    'profit_amount' => $calculation['profit_amount'] ?? 0,
                    'status' => $calculation['status'] ?? $bet->status,
                    'bet_result_type' => $calculation['bet_result_type'] ?? null,
                ]);

                $results['success']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Bet ID {$betId}: ".$e->getMessage();
                Log::error('Batch recalculation failed for bet', [
                    'bet_id' => $betId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
