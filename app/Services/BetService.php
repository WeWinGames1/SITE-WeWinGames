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

    /**
     * Get profit by year
     */
    public function getProfitByYear(): array
    {
        $profits = DB::table('bets')
            ->whereIn('status', ['won', 'lost'])
            ->selectRaw("YEAR(betting_date) as year, SUM(profit_amount) as total_profit")
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();

        $result = [];
        foreach ($profits as $profit) {
            $result[$profit->year] = round($profit->total_profit, 2);
        }

        return $result;
    }

    /**
     * Get ROI by year
     */
    public function getROIByYear(): array
    {
        $yearlyStats = DB::table('bets')
            ->whereIn('status', ['won', 'lost'])
            ->selectRaw("YEAR(betting_date) as year, SUM(wager_amount) as total_stake, SUM(profit_amount) as total_profit")
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();

        $result = [];
        foreach ($yearlyStats as $stat) {
            if ($stat->total_stake > 0) {
                $result[$stat->year] = round(($stat->total_profit / $stat->total_stake) * 100, 2);
            } else {
                $result[$stat->year] = 0.0;
            }
        }

        return $result;
    }

    /**
     * Get total ROI by subscription level
     */
    public function getTotalROIBySubscriptionLevel(?int $year = null): array
    {
        $query = DB::table('bets')->whereIn('status', ['won', 'lost']);
        
        if ($year) {
            $query->whereYear('betting_date', $year);
        }

        $stats = $query->selectRaw('membership, SUM(wager_amount) as total_stake, SUM(profit_amount) as total_profit')
            ->groupBy('membership')
            ->get();

        $result = [];
        foreach ($stats as $stat) {
            $roi = 0.0;
            if ($stat->total_stake > 0) {
                $roi = round(($stat->total_profit / $stat->total_stake) * 100, 2);
            }
            $result[$stat->membership] = [
                'total_stake' => round($stat->total_stake, 2),
                'total_profit' => round($stat->total_profit, 2),
                'roi' => $roi
            ];
        }

        return $result;
    }

    /**
     * Get profit and ROI by level
     */
    public function getProfitAndROIByLevel(?int $year = null): array
    {
        $query = DB::table('bets')->whereIn('status', ['won', 'lost']);
        
        if ($year) {
            $query->whereYear('betting_date', $year);
        }

        $stats = $query->selectRaw("membership, COUNT(*) as total_bets, SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as wins, SUM(wager_amount) as total_stake, SUM(profit_amount) as total_profit")
            ->groupBy('membership')
            ->get();

        $result = [];
        foreach ($stats as $stat) {
            $roi = 0.0;
            $winRate = 0.0;
            
            if ($stat->total_stake > 0) {
                $roi = round(($stat->total_profit / $stat->total_stake) * 100, 2);
            }
            
            if ($stat->total_bets > 0) {
                $winRate = round(($stat->wins / $stat->total_bets) * 100, 2);
            }
            
            $result[$stat->membership] = [
                'total_bets' => $stat->total_bets,
                'wins' => $stat->wins,
                'win_rate' => $winRate,
                'total_stake' => round($stat->total_stake, 2),
                'total_profit' => round($stat->total_profit, 2),
                'roi' => $roi
            ];
        }

        return $result;
    }

    /**
     * Get profit and ROI by sport
     */
    public function getProfitAndROIBySport(?int $year = null): array
    {
        $query = DB::table('bets')->whereIn('status', ['won', 'lost']);
        
        if ($year) {
            $query->whereYear('betting_date', $year);
        }

        $stats = $query->selectRaw("sports, COUNT(*) as total_bets, SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as wins, SUM(wager_amount) as total_stake, SUM(profit_amount) as total_profit")
            ->groupBy('sports')
            ->get();

        $result = [];
        foreach ($stats as $stat) {
            $roi = 0.0;
            $winRate = 0.0;
            
            if ($stat->total_stake > 0) {
                $roi = round(($stat->total_profit / $stat->total_stake) * 100, 2);
            }
            
            if ($stat->total_bets > 0) {
                $winRate = round(($stat->wins / $stat->total_bets) * 100, 2);
            }
            
            $result[$stat->sports] = [
                'total_bets' => $stat->total_bets,
                'wins' => $stat->wins,
                'win_rate' => $winRate,
                'total_stake' => round($stat->total_stake, 2),
                'total_profit' => round($stat->total_profit, 2),
                'roi' => $roi
            ];
        }

        return $result;
    }

    /**
     * Get average monthly profit
     */
    public function getAverageMonthlyProfit(): float
    {
        $monthlyProfits = DB::table('bets')
            ->whereIn('status', ['won', 'lost'])
            ->selectRaw("YEAR(betting_date) as year, MONTH(betting_date) as month, SUM(profit_amount) as total_profit")
            ->groupBy('year', 'month')
            ->get();

        if ($monthlyProfits->isEmpty()) {
            return 0.0;
        }

        $totalProfit = $monthlyProfits->sum('total_profit');
        $monthCount = $monthlyProfits->count();

        return round($totalProfit / $monthCount, 2);
    }

    /**
     * Get today's bets
     */
    public function getTodaysBets(): \Illuminate\Database\Eloquent\Collection
    {
        return Bet::whereDate('betting_date', today())
            ->orderBy('betting_date', 'desc')
            ->get();
    }

    /**
     * Get recent winning bets
     */
    public function getRecentWinningBets(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return Bet::where('status', 'Won')
            ->orderBy('betting_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get win/loss ratio
     */
    public function getWinLossRatio(): array
    {
        $stats = DB::table('bets')
            ->whereIn('status', ['won', 'lost'])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $wins = $stats->get('won', 0);
        $losses = $stats->get('lost', 0);
        $total = $wins + $losses;

        return [
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $total > 0 ? round(($wins / $total) * 100, 2) : 0.0
        ];
    }

    /**
     * Get win/loss ratio by year
     */
    public function getWinLossRatioByYear(int $year): array
    {
        $stats = DB::table('bets')
            ->whereIn('status', ['won', 'lost'])
            ->whereYear('betting_date', $year)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $wins = $stats->get('won', 0);
        $losses = $stats->get('lost', 0);
        $total = $wins + $losses;

        return [
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $total > 0 ? round(($wins / $total) * 100, 2) : 0.0
        ];
    }

    /**
     * Get profit by month
     */
    public function getProfitByMonth(int $year, int $month): float
    {
        $profit = DB::table('bets')
            ->whereIn('status', ['won', 'lost'])
            ->whereYear('betting_date', $year)
            ->whereMonth('betting_date', $month)
            ->sum('profit_amount');

        return round($profit, 2);
    }

    /**
     * Get ROI by month
     */
    public function getROIByMonth(int $year, int $month): float
    {
        $stats = DB::table('bets')
            ->whereIn('status', ['won', 'lost'])
            ->whereYear('betting_date', $year)
            ->whereMonth('betting_date', $month)
            ->selectRaw('SUM(wager_amount) as total_stake, SUM(profit_amount) as total_profit')
            ->first();

        if (!$stats || $stats->total_stake <= 0) {
            return 0.0;
        }

        return round(($stats->total_profit / $stats->total_stake) * 100, 2);
    }

    /**
     * Get win/loss ratio by month
     */
    public function getWinLossRatioByMonth(int $year, int $month): array
    {
        $stats = DB::table('bets')
            ->whereIn('status', ['won', 'lost'])
            ->whereYear('betting_date', $year)
            ->whereMonth('betting_date', $month)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $wins = $stats->get('won', 0);
        $losses = $stats->get('lost', 0);
        $total = $wins + $losses;

        return [
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $total > 0 ? round(($wins / $total) * 100, 2) : 0.0
        ];
    }

    /**
     * Get profit and ROI by year
     */
    public function getProfitAndROIByYear(): array
    {
        $yearlyStats = DB::table('bets')
            ->whereIn('status', ['won', 'lost'])
            ->selectRaw("YEAR(betting_date) as year, COUNT(*) as total_bets, SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as wins, SUM(wager_amount) as total_stake, SUM(profit_amount) as total_profit")
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();

        $result = [];
        foreach ($yearlyStats as $stat) {
            $roi = 0.0;
            $winRate = 0.0;
            
            if ($stat->total_stake > 0) {
                $roi = round(($stat->total_profit / $stat->total_stake) * 100, 2);
            }
            
            if ($stat->total_bets > 0) {
                $winRate = round(($stat->wins / $stat->total_bets) * 100, 2);
            }
            
            $result[$stat->year] = [
                'total_bets' => $stat->total_bets,
                'wins' => $stat->wins,
                'win_rate' => $winRate,
                'total_stake' => round($stat->total_stake, 2),
                'total_profit' => round($stat->total_profit, 2),
                'roi' => $roi
            ];
        }

        return $result;
    }

    /**
     * Get profit and ROI by month
     */
    public function getProfitAndROIByMonth(): array
    {
        $monthlyStats = DB::table('bets')
            ->whereIn('status', ['won', 'lost'])
            ->selectRaw("YEAR(betting_date) as year, MONTH(betting_date) as month, COUNT(*) as total_bets, SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as wins, SUM(wager_amount) as total_stake, SUM(profit_amount) as total_profit")
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(24) // Last 24 months
            ->get();

        $result = [];
        foreach ($monthlyStats as $stat) {
            $roi = 0.0;
            $winRate = 0.0;
            
            if ($stat->total_stake > 0) {
                $roi = round(($stat->total_profit / $stat->total_stake) * 100, 2);
            }
            
            if ($stat->total_bets > 0) {
                $winRate = round(($stat->wins / $stat->total_bets) * 100, 2);
            }
            
            $key = sprintf('%d-%02d', $stat->year, $stat->month);
            $result[$key] = [
                'year' => $stat->year,
                'month' => $stat->month,
                'total_bets' => $stat->total_bets,
                'wins' => $stat->wins,
                'win_rate' => $winRate,
                'total_stake' => round($stat->total_stake, 2),
                'total_profit' => round($stat->total_profit, 2),
                'roi' => $roi
            ];
        }

        return $result;
    }

    /**
     * Get all bets for export
     */
    public function getAllBetsForExport()
    {
        return Bet::orderBy('betting_date', 'desc')
            ->cursor();
    }

    /**
     * Get all bets
     */
    public function getAllBets()
    {
        return Bet::orderBy('betting_date', 'desc')
            ->get()
            ->toArray();
    }
}