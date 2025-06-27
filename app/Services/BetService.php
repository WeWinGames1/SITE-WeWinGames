<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\User;
use App\Repositories\Contracts\BetRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BetService
{
    public function __construct(
        private BetRepositoryInterface $betRepository
    ) {}

    /**
     * Create a new bet
     */
    public function createBet(User $user, array $data): array
    {
        try {
            DB::beginTransaction();

            // Calculate potential return
            $data['potential_return'] = $data['stake'] * $data['odds'];
            $data['user_id'] = $user->id;
            $data['placed_at'] = now();

            // Set default status if not provided
            if (!isset($data['status'])) {
                $data['status'] = 'pending';
            }

            // Create the bet
            $bet = $this->betRepository->create($data);

            // Load relationships
            $bet->load(['user', 'sport', 'game', 'operator']);

            Log::info('Bet created', [
                'bet_id' => $bet->id,
                'user_id' => $user->id,
                'amount' => $data['stake'],
            ]);

            DB::commit();

            return [
                'success' => true,
                'bet' => $bet,
                'message' => 'Bet placed successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create bet', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to place bet. Please try again.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update an existing bet
     */
    public function updateBet(Bet $bet, array $data): array
    {
        try {
            DB::beginTransaction();

            // Prevent updating settled bets
            if (in_array($bet->status, ['won', 'lost', 'void'])) {
                return [
                    'success' => false,
                    'message' => 'Cannot update a settled bet',
                ];
            }

            // Recalculate potential return if odds or stake changed
            if (isset($data['odds']) || isset($data['stake'])) {
                $odds = $data['odds'] ?? $bet->odds;
                $stake = $data['stake'] ?? $bet->stake;
                $data['potential_return'] = $stake * $odds;
            }

            // Update bet
            $this->betRepository->update($data, $bet->id);
            
            // Reload bet with relationships
            $bet = $this->betRepository->find($bet->id);
            $bet->load(['user', 'sport', 'game', 'operator']);

            Log::info('Bet updated', [
                'bet_id' => $bet->id,
                'updated_fields' => array_keys($data),
            ]);

            DB::commit();

            return [
                'success' => true,
                'bet' => $bet,
                'message' => 'Bet updated successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update bet', [
                'bet_id' => $bet->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update bet. Please try again.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a bet
     */
    public function deleteBet(Bet $bet): array
    {
        try {
            // Prevent deleting settled bets
            if (in_array($bet->status, ['won', 'lost'])) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete a settled bet',
                ];
            }

            $betId = $bet->id;
            $this->betRepository->delete($betId);

            Log::info('Bet deleted', [
                'bet_id' => $betId,
                'user_id' => $bet->user_id,
            ]);

            return [
                'success' => true,
                'message' => 'Bet deleted successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete bet', [
                'bet_id' => $bet->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete bet. Please try again.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Settle a bet
     */
    public function settleBet(Bet $bet, string $result): array
    {
        try {
            DB::beginTransaction();

            if ($bet->status !== 'pending') {
                return [
                    'success' => false,
                    'message' => 'Bet has already been settled',
                ];
            }

            $data = ['status' => $result];

            // Calculate profit based on result
            switch ($result) {
                case 'won':
                    $data['profit'] = $bet->potential_return - $bet->stake;
                    $data['winning_amount'] = $bet->potential_return;
                    break;
                case 'lost':
                    $data['profit'] = -$bet->stake;
                    $data['winning_amount'] = 0;
                    break;
                case 'void':
                case 'push':
                    $data['profit'] = 0;
                    $data['winning_amount'] = $bet->stake;
                    break;
                default:
                    throw new \InvalidArgumentException('Invalid bet result');
            }

            $data['settled_at'] = now();

            $this->betRepository->update($data, $bet->id);
            $bet->refresh();

            Log::info('Bet settled', [
                'bet_id' => $bet->id,
                'result' => $result,
                'profit' => $data['profit'],
            ]);

            DB::commit();

            return [
                'success' => true,
                'bet' => $bet,
                'message' => "Bet settled as {$result}",
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to settle bet', [
                'bet_id' => $bet->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to settle bet. Please try again.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get betting statistics
     */
    public function getStatistics(?int $userId = null): array
    {
        try {
            if ($userId) {
                // User-specific statistics
                $bets = $this->betRepository->getBetsByUser($userId);
                
                return [
                    'total_bets' => $bets->count(),
                    'winning_bets' => $bets->where('status', 'won')->count(),
                    'losing_bets' => $bets->where('status', 'lost')->count(),
                    'pending_bets' => $bets->where('status', 'pending')->count(),
                    'total_profit' => $this->betRepository->calculateProfitByUser($userId),
                    'total_stake' => $bets->sum('stake'),
                    'average_stake' => $bets->avg('stake') ?? 0,
                    'average_odds' => $bets->avg('odds') ?? 0,
                    'win_rate' => $this->calculateWinRate($bets),
                    'roi' => $this->calculateROI($bets),
                ];
            }

            // Global statistics
            return $this->betRepository->getBetStatistics();
        } catch (\Exception $e) {
            Log::error('Failed to get bet statistics', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'total_bets' => 0,
                'winning_bets' => 0,
                'losing_bets' => 0,
                'pending_bets' => 0,
                'total_profit' => 0,
                'average_stake' => 0,
                'average_odds' => 0,
                'win_rate' => 0,
            ];
        }
    }

    /**
     * Calculate win rate from bets collection
     */
    private function calculateWinRate($bets): float
    {
        $settledBets = $bets->whereIn('status', ['won', 'lost']);
        
        if ($settledBets->isEmpty()) {
            return 0.0;
        }

        $winningBets = $settledBets->where('status', 'won')->count();
        return round(($winningBets / $settledBets->count()) * 100, 2);
    }

    /**
     * Calculate ROI from bets collection
     */
    private function calculateROI($bets): float
    {
        $totalStake = $bets->whereIn('status', ['won', 'lost'])->sum('stake');
        
        if ($totalStake <= 0) {
            return 0.0;
        }

        $totalProfit = $bets->sum('profit');
        return round(($totalProfit / $totalStake) * 100, 2);
    }
}