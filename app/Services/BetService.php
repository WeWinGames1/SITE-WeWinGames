<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\User;
use App\Repositories\Contracts\BetRepositoryInterface;
use Carbon\Carbon;
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
            if (! isset($data['status'])) {
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
            if (in_array($bet->status, ['won', 'loss', 'void'])) {
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
            if (in_array($bet->status, ['won', 'loss'])) {
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
    public function settleBet(Bet $bet, string $result, ?array $positionData = null): array
    {
        try {
            DB::beginTransaction();

            if ($bet->status !== 'pending') {
                return [
                    'success' => false,
                    'message' => 'Bet has already been settled',
                ];
            }

            // Handle Each Way bets separately
            if ($bet->is_each_way) {
                // If position data is provided, use automatic determination
                if ($positionData && isset($positionData['position']) && isset($positionData['places_paid'])) {
                    $deadHeatInfo = null;
                    if (isset($positionData['players_tied']) && isset($positionData['spots_available'])) {
                        $deadHeatInfo = [
                            'players_tied' => $positionData['players_tied'],
                            'spots_available' => $positionData['spots_available'],
                        ];
                    }
                    
                    $data = $this->calculateEachWayPayoutWithDeadHeat(
                        $bet,
                        $positionData['position'],
                        $positionData['places_paid'],
                        $deadHeatInfo
                    );
                    
                    // Store position data
                    $data['finishing_position'] = $positionData['position'];
                    $data['places_paid'] = $positionData['places_paid'];
                    $data['position_numeric'] = $this->parsePosition($positionData['position']);
                } else {
                    // Fall back to manual result
                    $data = $this->settleEachWayBet($bet, $result);
                }
            } else {
                $data = ['status' => $result];

                // Calculate profit based on result
                switch ($result) {
                    case 'won':
                        $data['profit'] = $bet->potential_return - $bet->stake;
                        $data['winning_amount'] = $bet->potential_return;
                        break;
                    case 'loss':
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
            }

            $data['settled_at'] = now();

            $this->betRepository->update($data, $bet->id);
            $bet->refresh();

            Log::info('Bet settled', [
                'bet_id' => $bet->id,
                'result' => $result,
                'profit' => $data['profit'] ?? $data['profit_amount'] ?? 0,
                'is_each_way' => $bet->is_each_way,
                'position' => $positionData['position'] ?? null,
                'is_dead_heat' => $data['is_dead_heat'] ?? false,
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
                    'losing_bets' => $bets->where('status', 'loss')->count(),
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
        $settledBets = $bets->whereIn('status', ['won', 'loss']);

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
        $totalStake = $bets->whereIn('status', ['won', 'loss'])->sum('stake');

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
        // Get all bets and group by year in PHP for database compatibility
        $bets = Bet::whereIn('status', ['won', 'loss'])
            ->select('betting_date', 'profit_amount')
            ->get();

        // Group by year using Carbon
        $yearlyProfits = $bets->groupBy(function ($bet) {
            return Carbon::parse($bet->betting_date)->year;
        })->map(function ($yearBets) {
            return round($yearBets->sum('profit_amount'), 2);
        })->sortKeysDesc();

        return $yearlyProfits->toArray();
    }

    /**
     * Get ROI by year
     */
    public function getROIByYear(): array
    {
        // Get all bets and group by year in PHP for database compatibility
        $bets = Bet::whereIn('status', ['won', 'loss'])
            ->select('betting_date', 'wager_amount', 'profit_amount')
            ->get();

        // Group by year
        $yearlyGroups = $bets->groupBy(function ($bet) {
            return Carbon::parse($bet->betting_date)->year;
        });

        $result = [];
        foreach ($yearlyGroups as $year => $yearBets) {
            $totalStake = $yearBets->sum('wager_amount');
            $totalProfit = $yearBets->sum('profit_amount');

            if ($totalStake > 0) {
                $result[$year] = round(($totalProfit / $totalStake) * 100, 2);
            } else {
                $result[$year] = 0.0;
            }
        }

        // Sort by year descending
        krsort($result);

        return $result;
    }

    /**
     * Get total ROI by subscription level
     */
    public function getTotalROIBySubscriptionLevel(?int $year = null): array
    {
        $query = DB::table('bets')->whereIn('status', ['won', 'loss']);

        if ($year) {
            $query->whereYear('betting_date', $year);
        }

        // Use UPPER() to normalize case-sensitive membership values
        $stats = $query->selectRaw('UPPER(membership) as membership, SUM(wager_amount) as total_stake, SUM(profit_amount) as total_profit')
            ->groupBy(DB::raw('UPPER(membership)'))
            ->get();

        $result = [];
        foreach ($stats as $stat) {
            $roi = 0.0;
            if ($stat->total_stake > 0) {
                $roi = round(($stat->total_profit / $stat->total_stake) * 100, 2);
            }
            
            // Normalize the membership level to proper case (capitalize first letter)
            $normalizedLevel = ucfirst(strtolower($stat->membership));
            
            // Return just the ROI value for each membership level
            $result[$normalizedLevel] = $roi;
        }

        return $result;
    }

    /**
     * Get profit and ROI by level
     */
    public function getProfitAndROIByLevel(?int $year = null): array
    {
        $query = DB::table('bets')->whereIn('status', ['won', 'loss']);

        if ($year) {
            $query->whereYear('betting_date', $year);
        }

        // Use UPPER() to normalize case-sensitive membership values
        $stats = $query->selectRaw("UPPER(membership) as membership, COUNT(*) as total_bets, SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as wins, SUM(wager_amount) as total_stake, SUM(profit_amount) as total_profit")
            ->groupBy(DB::raw('UPPER(membership)'))
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

            // Normalize the membership level to proper case (capitalize first letter)
            $normalizedLevel = ucfirst(strtolower($stat->membership));

            // Return array format expected by tables
            $result[] = [
                'level' => $normalizedLevel,
                'profit' => round($stat->total_profit, 2),
                'roi' => $roi,
            ];
        }

        // Sort by the expected order: Bronze, Silver, Gold, Platinum
        $levelOrder = ['Bronze' => 1, 'Silver' => 2, 'Gold' => 3, 'Platinum' => 4];
        usort($result, function($a, $b) use ($levelOrder) {
            $orderA = $levelOrder[$a['level']] ?? 999;
            $orderB = $levelOrder[$b['level']] ?? 999;
            return $orderA <=> $orderB;
        });

        return $result;
    }

    /**
     * Get profit and ROI by sport
     */
    public function getProfitAndROIBySport(?int $year = null): array
    {
        $query = DB::table('bets')->whereIn('status', ['won', 'loss']);

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

            // Return array format expected by tables and charts
            $result[] = [
                'sport' => $stat->sports,
                'profit' => round($stat->total_profit, 2),
                'roi' => $roi,
            ];
        }

        return $result;
    }

    /**
     * Get average monthly profit
     */
    public function getAverageMonthlyProfit(): float
    {
        // Get all bets and group by month in PHP to avoid SQL complexity
        $bets = Bet::whereIn('status', ['won', 'loss'])
            ->select('betting_date', 'profit_amount')
            ->get();

        if ($bets->isEmpty()) {
            return 0.0;
        }

        // Group by month using Carbon
        $monthlyProfits = $bets->groupBy(function ($bet) {
            return Carbon::parse($bet->betting_date)->format('Y-m');
        })->map(function ($monthBets) {
            return $monthBets->sum('profit_amount');
        });

        if ($monthlyProfits->isEmpty()) {
            return 0.0;
        }

        return round($monthlyProfits->avg(), 2);
    }

    /**
     * Get today's bets filtered by user's subscription tier
     * Shows bets where:
     * - betting_date = today OR
     * - game_date >= today
     * Filters based on user's subscription level
     */
    public function getTodaysBets(): \Illuminate\Database\Eloquent\Collection
    {
        $today = today()->toDateString();
        $user = auth()->user();
        
        $query = Bet::where(function ($query) use ($today) {
                $query->whereDate('betting_date', $today)
                      ->orWhereDate('game_date', '>=', $today);
            });
        
        // Apply tier-based filtering if user is authenticated
        if ($user) {
            $userTier = $user->getCurrentTier() ?? 'Bronze';
            
            // Determine which levels to show based on user's tier
            $visibleLevels = [];
            switch (strtolower($userTier)) {
                case 'platinum':
                    $visibleLevels = ['Bronze', 'Silver', 'Gold', 'Platinum'];
                    break;
                case 'gold':
                    $visibleLevels = ['Bronze', 'Silver', 'Gold'];
                    break;
                case 'silver':
                    $visibleLevels = ['Bronze', 'Silver'];
                    break;
                default: // Bronze or no subscription
                    $visibleLevels = ['Bronze'];
                    break;
            }
            
            $query->whereIn('level', $visibleLevels);
        } else {
            // Non-authenticated users only see Bronze picks
            $query->where('level', 'Bronze');
        }
        
        return $query->orderBy('game_date', 'asc')
            ->orderBy('betting_date', 'asc')
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
            ->whereIn('status', ['won', 'loss'])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $wins = $stats->get('won', 0);
        $losses = $stats->get('loss', 0);
        $total = $wins + $losses;

        return [
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $total > 0 ? round(($wins / $total) * 100, 2) : 0.0,
        ];
    }

    /**
     * Get win/loss ratio by year
     */
    public function getWinLossRatioByYear(int $year): array
    {
        $stats = DB::table('bets')
            ->whereIn('status', ['won', 'loss'])
            ->whereYear('betting_date', $year)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $wins = $stats->get('won', 0);
        $losses = $stats->get('loss', 0);
        $total = $wins + $losses;

        return [
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $total > 0 ? round(($wins / $total) * 100, 2) : 0.0,
        ];
    }

    /**
     * Get profit by month
     */
    public function getProfitByMonth(int $year, int $month): float
    {
        $profit = DB::table('bets')
            ->whereIn('status', ['won', 'loss'])
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
            ->whereIn('status', ['won', 'loss'])
            ->whereYear('betting_date', $year)
            ->whereMonth('betting_date', $month)
            ->selectRaw('SUM(wager_amount) as total_stake, SUM(profit_amount) as total_profit')
            ->first();

        if (! $stats || $stats->total_stake <= 0) {
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
            ->whereIn('status', ['won', 'loss'])
            ->whereYear('betting_date', $year)
            ->whereMonth('betting_date', $month)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $wins = $stats->get('won', 0);
        $losses = $stats->get('loss', 0);
        $total = $wins + $losses;

        return [
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $total > 0 ? round(($wins / $total) * 100, 2) : 0.0,
        ];
    }

    /**
     * Get profit and ROI by year
     */
    public function getProfitAndROIByYear(): array
    {
        // Get all bets and process in PHP for database compatibility
        $bets = Bet::whereIn('status', ['won', 'loss'])
            ->select('betting_date', 'status', 'wager_amount', 'profit_amount')
            ->get();

        // Group by year
        $yearlyGroups = $bets->groupBy(function ($bet) {
            return Carbon::parse($bet->betting_date)->year;
        });

        $result = [];
        foreach ($yearlyGroups as $year => $yearBets) {
            $totalBets = $yearBets->count();
            $wins = $yearBets->where('status', 'won')->count();
            $totalStake = $yearBets->sum('wager_amount');
            $totalProfit = $yearBets->sum('profit_amount');

            $roi = 0.0;
            $winRate = 0.0;

            if ($totalStake > 0) {
                $roi = round(($totalProfit / $totalStake) * 100, 2);
            }

            if ($totalBets > 0) {
                $winRate = round(($wins / $totalBets) * 100, 2);
            }

            // Return array format expected by tables and charts
            $result[] = [
                'year' => $year,
                'profit' => round($totalProfit, 2),
                'roi' => $roi,
            ];
        }

        // Sort by year descending
        usort($result, function($a, $b) {
            return $b['year'] - $a['year'];
        });

        return $result;
    }

    /**
     * Get profit and ROI by month
     */
    public function getProfitAndROIByMonth(): array
    {
        // Get bets from the last 24 months
        $cutoffDate = Carbon::now()->subMonths(24)->startOfMonth();

        $bets = Bet::whereIn('status', ['won', 'loss'])
            ->where('betting_date', '>=', $cutoffDate)
            ->select('betting_date', 'status', 'wager_amount', 'profit_amount')
            ->orderBy('betting_date', 'desc')
            ->get();

        // Group by month in PHP
        $monthlyGroups = $bets->groupBy(function ($bet) {
            return Carbon::parse($bet->betting_date)->format('Y-m');
        });

        $result = [];
        foreach ($monthlyGroups as $monthKey => $monthBets) {
            $date = Carbon::createFromFormat('Y-m', $monthKey);
            $wins = $monthBets->where('status', 'won')->count();
            $totalBets = $monthBets->count();
            $totalStake = $monthBets->sum('wager_amount');
            $totalProfit = $monthBets->sum('profit_amount');

            $roi = 0.0;
            $winRate = 0.0;

            if ($totalStake > 0) {
                $roi = round(($totalProfit / $totalStake) * 100, 2);
            }

            if ($totalBets > 0) {
                $winRate = round(($wins / $totalBets) * 100, 2);
            }

            // Return array format expected by tables and charts
            $result[] = [
                'month' => $date->format('Y-m'),
                'profit' => round($totalProfit, 2),
                'roi' => $roi,
            ];
        }

        // Sort by month descending
        usort($result, function($a, $b) {
            return strcmp($b['month'], $a['month']);
        });

        // Limit to 24 months
        return array_slice($result, 0, 24);
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

    /**
     * Get ticker bets - Shows different content based on user authentication status.
     * - Public users: Only see older/expired picks
     * - Logged in users: See current picks based on their tier, filled with older picks if needed
     */
    public function getTickerBets()
    {
        $user = auth()->user();
        $now = now();
        $bets = collect();

        // Get preferred sports from sport_preferences table
        $preferredSports = \App\Models\SportPreference::active()
            ->orderBy('priority', 'asc')
            ->pluck('sport_name')
            ->toArray();

        if (!$user) {
            // PUBLIC USER: Only show older/expired picks
            $bets = Bet::query()
                ->select('id', 'sport', 'game', 'wager_name', 'odds', 'level', 'game_date', 'betting_date')
                ->whereIn('status', ['won', 'loss']) // Only settled bets
                ->where('game_date', '<', $now) // Only past games
                ->when(!empty($preferredSports), function ($query) use ($preferredSports) {
                    // Sort by preferred sports using a CASE statement with safe bindings
                    $cases = [];
                    $bindings = [];
                    foreach ($preferredSports as $index => $sport) {
                        $cases[] = "WHEN sport = ? THEN " . $index;
                        $bindings[] = $sport;
                    }
                    $caseStatement = "CASE " . implode(" ", $cases) . " ELSE 999 END";
                    return $query->orderByRaw($caseStatement, $bindings);
                })
                ->orderBy('game_date', 'desc')
                ->limit(10)
                ->get();
        } else {
            // LOGGED IN USER: Show current picks based on tier
            $userTier = $user->getCurrentTier() ?? 'Bronze';
            
            // Determine which levels to show based on user's tier
            $visibleLevels = [];
            switch (strtolower($userTier)) {
                case 'platinum':
                    $visibleLevels = ['Bronze', 'Silver', 'Gold', 'Platinum'];
                    break;
                case 'gold':
                    $visibleLevels = ['Bronze', 'Silver', 'Gold'];
                    break;
                case 'silver':
                    $visibleLevels = ['Bronze', 'Silver'];
                    break;
                default: // Bronze or no subscription
                    $visibleLevels = ['Bronze'];
                    break;
            }

            // First, get current (pending) picks
            $currentBets = Bet::query()
                ->select('id', 'sport', 'game', 'wager_name', 'odds', 'level', 'game_date', 'betting_date')
                ->where('status', 'pending')
                ->where(function($query) use ($visibleLevels) {
                    $query->whereIn('level', $visibleLevels)
                          ->orWhereIn('level', array_map('strtolower', $visibleLevels));
                })
                ->where('game_date', '>=', $now) // Future games
                ->when(!empty($preferredSports), function ($query) use ($preferredSports) {
                    // Sort by preferred sports using a CASE statement with safe bindings
                    $cases = [];
                    $bindings = [];
                    foreach ($preferredSports as $index => $sport) {
                        $cases[] = "WHEN sport = ? THEN " . $index;
                        $bindings[] = $sport;
                    }
                    $caseStatement = "CASE " . implode(" ", $cases) . " ELSE 999 END";
                    return $query->orderByRaw($caseStatement, $bindings);
                })
                ->orderBy('game_date', 'asc')
                ->limit(10)
                ->get();

            $bets = $currentBets;

            // If we have less than 10 current picks, fill with older picks
            if ($currentBets->count() < 10) {
                $needed = 10 - $currentBets->count();
                
                $olderBets = Bet::query()
                    ->select('id', 'sport', 'game', 'wager_name', 'odds', 'level', 'game_date', 'betting_date')
                    ->whereIn('status', ['won', 'loss']) // Settled bets
                    ->where(function($query) use ($visibleLevels) {
                    $query->whereIn('level', $visibleLevels)
                          ->orWhereIn('level', array_map('strtolower', $visibleLevels));
                })
                    ->where('game_date', '<', $now) // Past games
                    ->whereNotIn('id', $currentBets->pluck('id')) // Exclude already selected
                    ->when(!empty($preferredSports), function ($query) use ($preferredSports) {
                        // Sort by preferred sports using a CASE statement with safe bindings
                        $cases = [];
                        $bindings = [];
                        foreach ($preferredSports as $index => $sport) {
                            $cases[] = "WHEN sport = ? THEN " . $index;
                            $bindings[] = $sport;
                        }
                        $caseStatement = "CASE " . implode(" ", $cases) . " ELSE 999 END";
                        return $query->orderByRaw($caseStatement, $bindings);
                    })
                    ->orderBy('game_date', 'desc')
                    ->limit($needed)
                    ->get();

                $bets = $currentBets->concat($olderBets);
            }
        }

        return $bets;
    }

    /**
     * Settle an Each Way bet
     */
    private function settleEachWayBet(Bet $bet, string $result): array
    {
        $halfStake = $bet->each_way_stake ?: ($bet->wager_amount / 2);
        $placeFraction = $bet->place_fraction ?: 0.2; // Default 1/5
        
        // Calculate place odds
        $placeOdds = $this->calculatePlaceOdds($bet->odds, $placeFraction);
        
        $data = ['status' => $result];
        
        switch ($result) {
            case 'won':
                // Both win and place parts win
                $winPayout = $this->calculateAmericanOddsPayout($halfStake, $bet->odds);
                $placePayout = $this->calculateAmericanOddsPayout($halfStake, $placeOdds);
                
                $data['winning_amount'] = $winPayout + $placePayout;
                $data['place_payout'] = $placePayout;
                $data['profit_amount'] = ($winPayout + $placePayout) - $bet->wager_amount;
                break;
                
            case 'placed':
                // Only place part wins
                $placePayout = $this->calculateAmericanOddsPayout($halfStake, $placeOdds);
                
                $data['winning_amount'] = $placePayout;
                $data['place_payout'] = $placePayout;
                $data['profit_amount'] = $placePayout - $bet->wager_amount;
                break;
                
            case 'loss':
                // Both parts lose
                $data['winning_amount'] = 0;
                $data['place_payout'] = 0;
                $data['profit_amount'] = -$bet->wager_amount;
                break;
                
            case 'void':
            case 'push':
                // Return full stake
                $data['winning_amount'] = $bet->wager_amount;
                $data['profit_amount'] = 0;
                break;
                
            default:
                throw new \InvalidArgumentException('Invalid bet result for Each Way bet');
        }
        
        return $data;
    }
    
    /**
     * Calculate place odds from win odds and place fraction
     */
    private function calculatePlaceOdds($americanOdds, $placeFraction): float
    {
        if ($americanOdds > 0) {
            // Positive odds: +2200 with 1/5 = +440
            return $americanOdds * $placeFraction;
        } else {
            // Negative odds: Convert to decimal, apply fraction, convert back
            $decimal = ($americanOdds < 0) ? (100 / abs($americanOdds)) + 1 : ($americanOdds / 100) + 1;
            $placeDecimal = 1 + (($decimal - 1) * $placeFraction);
            
            // Convert back to American
            if ($placeDecimal >= 2) {
                return ($placeDecimal - 1) * 100;
            } else {
                return -100 / ($placeDecimal - 1);
            }
        }
    }
    
    /**
     * Calculate payout for American odds
     */
    private function calculateAmericanOddsPayout($stake, $odds): float
    {
        if ($odds > 0) {
            // Positive odds: stake * (odds/100) + stake
            return $stake * ($odds / 100) + $stake;
        } else {
            // Negative odds: stake * (100/abs(odds)) + stake
            return $stake * (100 / abs($odds)) + $stake;
        }
    }
    
    /**
     * Determine Each Way bet status based on finishing position
     */
    public function determineEachWayStatus(string $position, int $placesPaid): string
    {
        $numericPosition = $this->parsePosition($position);
        
        if ($numericPosition === null) {
            // Handle special cases like MC (Missed Cut), WD (Withdrawn), DQ (Disqualified)
            if (in_array(strtoupper($position), ['MC', 'WD', 'DQ', 'CUT', 'DNS', 'DNF'])) {
                return 'loss';
            }
            return 'loss'; // Default to lost if we can't parse
        }
        
        if ($numericPosition === 1) {
            return 'won'; // Outright win
        } elseif ($numericPosition <= $placesPaid) {
            return 'placed'; // Place finish
        } else {
            return 'loss'; // Outside paying places
        }
    }
    
    /**
     * Parse position string to numeric value
     * Handles formats like "1", "T5", "2nd", "3rd", etc.
     */
    public function parsePosition(string $position): ?int
    {
        $position = strtoupper(trim($position));
        
        // Handle tied positions (T5, T10, etc.)
        if (preg_match('/^T(\d+)$/i', $position, $matches)) {
            return (int) $matches[1];
        }
        
        // Handle ordinal positions (1st, 2nd, 3rd, etc.)
        if (preg_match('/^(\d+)(st|nd|rd|th)$/i', $position, $matches)) {
            return (int) $matches[1];
        }
        
        // Handle plain numeric positions
        if (is_numeric($position)) {
            return (int) $position;
        }
        
        // Handle special text positions
        $textPositions = [
            'FIRST' => 1, '1ST' => 1,
            'SECOND' => 2, '2ND' => 2,
            'THIRD' => 3, '3RD' => 3,
            'FOURTH' => 4, '4TH' => 4,
            'FIFTH' => 5, '5TH' => 5,
            'SIXTH' => 6, '6TH' => 6,
            'SEVENTH' => 7, '7TH' => 7,
            'EIGHTH' => 8, '8TH' => 8,
            'NINTH' => 9, '9TH' => 9,
            'TENTH' => 10, '10TH' => 10,
        ];
        
        if (isset($textPositions[$position])) {
            return $textPositions[$position];
        }
        
        return null; // Unable to parse
    }
    
    /**
     * Calculate dead heat reduction factor
     */
    public function calculateDeadHeatReduction(int $playersTied, float $spotsAvailable): float
    {
        if ($playersTied <= 0 || $spotsAvailable <= 0) {
            return 0; // Invalid input
        }
        
        // Reduction factor = available spots / players tied
        return min(1, $spotsAvailable / $playersTied);
    }
    
    /**
     * Apply dead heat reduction to a payout
     */
    public function applyDeadHeatToPlacePayout(float $basePayout, float $stake, float $reductionFactor): float
    {
        // For dead heat, only the profit portion is reduced, not the stake return
        $profit = $basePayout - $stake;
        $reducedProfit = $profit * $reductionFactor;
        
        // Return stake + reduced profit
        return $stake + $reducedProfit;
    }
    
    /**
     * Calculate Each Way payout with dead heat consideration
     */
    public function calculateEachWayPayoutWithDeadHeat(Bet $bet, string $position, int $placesPaid, ?array $deadHeatInfo = null): array
    {
        $status = $this->determineEachWayStatus($position, $placesPaid);
        $halfStake = $bet->each_way_stake ?: ($bet->wager_amount / 2);
        $placeFraction = $bet->place_fraction ?: 0.2; // Default 1/5
        $placeOdds = $this->calculatePlaceOdds($bet->odds, $placeFraction);
        
        $result = [
            'status' => $status,
            'bet_result_type' => $status === 'won' ? 'won_outright' : ($status === 'placed' ? 'placed' : 'loss'),
            'winning_amount' => 0,
            'place_payout' => 0,
            'profit_amount' => 0,
            'is_dead_heat' => false,
            'dead_heat_players' => null,
            'dead_heat_spots' => null,
        ];
        
        switch ($status) {
            case 'won':
                // Both win and place parts win (no dead heat on outright wins)
                $winPayout = $this->calculateAmericanOddsPayout($halfStake, $bet->odds);
                $placePayout = $this->calculateAmericanOddsPayout($halfStake, $placeOdds);
                
                $result['winning_amount'] = $winPayout + $placePayout;
                $result['place_payout'] = $placePayout;
                $result['profit_amount'] = ($winPayout + $placePayout) - $bet->wager_amount;
                $result['bet_result_type'] = 'won_outright';
                break;
                
            case 'placed':
                // Only place part wins - check for dead heat
                $placePayout = $this->calculateAmericanOddsPayout($halfStake, $placeOdds);
                
                if ($deadHeatInfo && isset($deadHeatInfo['players_tied']) && isset($deadHeatInfo['spots_available'])) {
                    // Apply dead heat reduction
                    $reductionFactor = $this->calculateDeadHeatReduction(
                        $deadHeatInfo['players_tied'],
                        $deadHeatInfo['spots_available']
                    );
                    
                    $placePayout = $this->applyDeadHeatToPlacePayout($placePayout, $halfStake, $reductionFactor);
                    
                    $result['is_dead_heat'] = true;
                    $result['dead_heat_players'] = $deadHeatInfo['players_tied'];
                    $result['dead_heat_spots'] = $deadHeatInfo['spots_available'];
                    $result['bet_result_type'] = 'placed_dead_heat';
                }
                
                $result['winning_amount'] = $placePayout;
                $result['place_payout'] = $placePayout;
                $result['profit_amount'] = $placePayout - $bet->wager_amount;
                break;
                
            case 'loss':
                // Both parts lose
                $result['winning_amount'] = 0;
                $result['place_payout'] = 0;
                $result['profit_amount'] = -$bet->wager_amount;
                $result['bet_result_type'] = 'loss';
                break;
        }
        
        return $result;
    }
}
