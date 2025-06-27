<?php

namespace App\Services;

use App\Events\NewBet;
use App\Models\Bet;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
/**
 * Class BetService.
 */
class BetService
{
    /**
     * Create a new bet from the request data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\Bet
     */
    public function createBetFromRequest(Request $request): Bet {
        // Validate the request data
        $validatedData = $request->validate([
            'sports' => 'required|string',
            'league' => 'required|string',
            'matches' => 'required|string',
            'team_one' => 'required|string',
            'team_two' => 'required|string',
            'team_one_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'team_two_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'markets' => 'required|string',
            'wager_amount' => 'required|numeric',
            'status' => 'required|string',
            'tips' => 'required|string',
            'betting_date' => 'required|date',
            'wager_odds' => 'required|numeric',
            'membership' => 'required|string',
            'referrer' => 'nullable|string',
            'place_fraction' => 'nullable|numeric|min:0|max:1',
        ]);

        // Handle file uploads
        if ($request->hasFile('team_one_logo')) {
            $validatedData['team_one_logo'] = Storage::url($request->file('team_one_logo')->store('team_logos', 'public'));
        }

        if ($request->hasFile('team_two_logo')) {
            $validatedData['team_two_logo'] = Storage::url($request->file('team_two_logo')->store('team_logos', 'public'));
        }

        // Create and return the bet
        $bet = Bet::create($validatedData);
        NewBet::dispatch($bet); // Dispatch the event to notify users
        return $bet;
    }

    /**
     * Update a bet and calculate ROI.
     *
     * @param  \App\Models\Bet  $bet
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\Bet
     */
    public function updateBetAndCalculateROI(Bet $bet, Request $request): Bet {
        // Validate the request data
        $validatedData = $request->validate([
            'status' => 'nullable|string|in:Pending,Won,Lost,Push',
            'betting_date' => 'nullable|date',
            'team_one_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'team_two_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'referrer' => 'nullable|string',
        ]);
        //dd($validatedData);
        // Handle file uploads
        if ($request->hasFile('team_one_logo')) {
            $validatedData['team_one_logo'] = Storage::url($request->file('team_one_logo')->store('team_logos', 'public'));
        }
    
        if ($request->hasFile('team_two_logo')) {
            $validatedData['team_two_logo'] = Storage::url($request->file('team_two_logo')->store('team_logos', 'public'));
        }
    
        $bet->status =$validatedData['status'] ?? $bet->status;
        //dd($bet);
        $bet->betting_date = $validatedData['betting_date'] ?? $bet->betting_date;
        $bet->team_one_logo = $validatedData['team_one_logo'] ?? $bet->team_one_logo;
        $bet->team_two_logo = $validatedData['team_two_logo'] ?? $bet->team_two_logo;
        $bet->save();
        // Calculate winnings and ROI if the bet is won
        if ($bet->markets === 'each way bet') {
            // Example: $20 stake, 100-1 odds, 1/4 place terms, won or placed
            $placeFraction = 0.25; // You may want to store this per bet/event
            $won = $bet->status === 'Won';
            $placed = $bet->status === 'Placed' || $won; // If won, also placed

            $result = $this->calculateEachWayPayout(
                $bet->wager_amount,
                $bet->wager_odds,
                $placeFraction,
                $won,
                $placed
            );

            $bet->winning_amount = $result['total'];
            $bet->profit_amount = $result['total'] - $bet->wager_amount;
            $bet->roi = $bet->wager_amount > 0
                ? round(($bet->profit_amount / $bet->wager_amount) * 100, 2)
                : 0;
        } else {
        if ($bet->status === 'Won') {
            // Ensure wager amount and odds are valid
            if ($bet->wager_amount > 0 && $bet->wager_odds !== 0) {
                // Calculate winning amount based on odds
                if ($bet->wager_odds > 0) {
                    // Positive odds (e.g., +150)
                    $bet->winning_amount = $bet->wager_amount * ($bet->wager_odds / 100);
                } else {
                    // Negative odds (e.g., -110)
                    $bet->winning_amount = $bet->wager_amount / (abs($bet->wager_odds) / 100);
                }

                // Calculate profit and ROI
                $bet->profit_amount = $bet->winning_amount;
                $bet->roi = round(($bet->profit_amount / $bet->wager_amount) * 100, 2);
            } else {
                // Invalid wager amount or odds
                $bet->winning_amount = 0;
                $bet->profit_amount = 0;
                $bet->roi = 0;
            }
        } else {
            // If the bet is lost, set winnings, profit, and ROI to 0
            
            $bet->winning_amount = 0;
            $bet->profit_amount = -1*$bet->wager_amount;
            $bet->roi = -1 * round(($bet->profit_amount / $bet->wager_amount) * 100, 2); // ROI is negative for losses
        }
    }
        // Save the updated bet
        $bet->save();

        return $bet;
    }

    /**
     * Get total ROI by subscription level, optionally filtered by year.
     *
     * @param int|null $year
     * @return array
     */
    public function getTotalROIBySubscriptionLevel($year = null): array {
        $levelsOrder = ['Bronze', 'Silver', 'Gold', 'Platinum'];

        $query = Bet::query();
        if ($year) {
            $query->whereYear('betting_date', $year);
        } else {
            $query->whereYear('betting_date', now()->year);
        }

        // Get ROI data and normalize level names
        $data = $query->selectRaw('membership, SUM(profit_amount) as total_profit, SUM(wager_amount) as total_wager, SUM(winning_amount) as total_winning')
            ->groupBy('membership')
            ->get()
            ->map(fn($item) => [
                'level' => ucfirst(strtolower($item->membership)),
                'roi' => $item->total_wager == 0 ? 0 : round($item->total_winning / $item->total_wager, 2) * 100,
            ])
            ->toArray();

        // Remove duplicates by level (keep the first occurrence)
        $data = collect($data)->unique('level')->values()->all();

        // Sort by custom order
        usort($data, function ($a, $b) use ($levelsOrder) {
            $posA = array_search($a['level'], $levelsOrder);
            $posB = array_search($b['level'], $levelsOrder);
            return $posA <=> $posB;
        });

        // Return as [level => roi] for compatibility
        return collect($data)->mapWithKeys(fn($item) => [$item['level'] => $item['roi']])->toArray();
    }

    /**
     * Get all bets with ROI calculated, sorted by latest betting_date first.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllBets(): \Illuminate\Database\Eloquent\Collection {
        return Bet::orderBy('betting_date', 'desc')->get()->map(function ($bet) {
            if ($bet->wager_amount > 0) {
                $bet->roi = round((($bet->winning_amount - $bet->wager_amount) / $bet->wager_amount) * 100, 2);
            } else {
                $bet->roi = 0; // Set ROI to 0 if wager amount is invalid
            }
            return $bet;
        });
    }

    /**
     * Get all bets for today's games, optionally filtered by membership level.
     *
     * @param  string|null  $membership
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTodaysBets(?string $membership = null): \Illuminate\Database\Eloquent\Collection{
       
        $query = Bet::whereTodayOrAfter('betting_date')
        ->orderBy('sports')
        ->orderBy('betting_date', 'desc');

        // Apply membership filter if provided
        if ($membership) {
            $query->where('membership', $membership);
        }

        return $query->get()->map(function ($bet) {
            if ($bet->wager_amount > 0) {
                $bet->roi = round((($bet->winning_amount - $bet->wager_amount) / $bet->wager_amount) * 100, 2);
            } else {
                $bet->roi = 0; // Set ROI to 0 if wager amount is invalid
            }
            return $bet;
        });
    }

    /**
     * Delete a bet.
     *
     * @param  \App\Models\Bet  $bet
     * @return void
     */
    public function deleteBet(Bet $bet): void{
        $bet->delete();
    }
    /**
     * Get average ROI for the current year.
     *
     * @return float
     */
    public function getROIForCurrentYear($year): array {
        
        $totalProfit = Bet::whereYear('betting_date', $year)->sum('profit_amount');
        $totalWager = Bet::whereYear('betting_date', $year)->sum('wager_amount');
        $roi = $totalProfit/($totalWager);
        
        return [$year => round($roi ?? 0, 2)];
        
    }

    /**
     * Get average ROI for the previous year.
     *
     * @return float
     */
    public function getROIForLastYear(): float{
        $lastYear = now()->subYear()->year;
        $totalProfit = Bet::whereYear('betting_date', $lastYear)->sum('profit_amount');
        $totalWager = Bet::whereYear('betting_date', $lastYear)->sum('wager_amount');
        $roi = $totalProfit/$totalWager;
        return round($roi ?? 0, 2);
    }
    /**
     * Get average ROI grouped by year.
     *
     * @return array
     */
    public function getROIByYear(): array {
        return Bet::selectRaw('YEAR(betting_date) as year, SUM(profit_amount) as total_profit, SUM(wager_amount) as total_wager,SUM(winning_amount) as total_winning')
            ->groupByRaw('YEAR(betting_date)')
            ->orderBy('year')
            ->get()
            ->mapWithKeys(fn($item) => [$item->year => round($item->total_winning/$item->total_wager, 2)*100])
            ->toArray();
    }
    /**
     * Get total profit grouped by year.
     *
     * @return array
     */
    public function getProfitByYear(): array {
        $bets =  Bet::selectRaw('YEAR(betting_date) as year, SUM(profit_amount) as total_profit, SUM(wager_amount) as total_wager, SUM(winning_amount) as total_winning')
            ->groupByRaw('YEAR(betting_date)')
            ->orderBy('year')
            ->get()
            ->mapWithKeys(fn($item) => [$item->year => round($item->total_winning , 2)])
            ->toArray();
            //dd($bets);
            return $bets;
    }
  
    /**
     * Get total profit and ROI grouped by sport for a given year.
     *
     * @param int|null $year
     * @return array
     */
    public function getProfitAndROIBySport(?int $year = null): array {
        $year = $year ?? now()->year;

        $bets = Bet::selectRaw('sports, SUM(profit_amount) as total_profit, SUM(wager_amount) as total_wager, SUM(winning_amount) as total_winning')
            ->whereYear('betting_date', $year)
            ->groupBy('sports')
            ->orderBy('total_winning', 'desc')
            ->get()
            ->map(function ($item) {
                
                return [
                    'sport' => $item->sports,
                    'profit' => round($item->total_winning, 2),
                    'roi' => round($item->total_winning/$item->total_wager, 2) *100,
                ];
            })
            ->toArray();
            return $bets;
    }
    /**
     * Get average monthly profit for a given year.
     *
     * @param int|null $year
     * @return float
     */
    public function getAverageMonthlyProfit(?int $year = null): float {
        $year = $year ?? now()->year;

        // Get total profit for the year
        $totalProfit = Bet::whereYear('betting_date', $year)->sum('profit_amount');

        // Get all months with bets in the year
        $monthsWithBets = Bet::whereYear('betting_date', $year)
            ->selectRaw('DISTINCT MONTH(betting_date) as month')
            ->pluck('month')
            ->count();

        // Avoid division by zero
        if ($monthsWithBets === 0) {
            return 0;
        }

        // Calculate average monthly profit
        return round($totalProfit / $monthsWithBets, 2);
    }
    /**
     * Get win/loss ratio for a given year.
     *
     * @param int|null $year
     * @return float|null  // Returns ratio as a decimal (e.g., 0.75 for 75% win rate), or null if no bets
     */
    public function getWinLossRatio(?int $year = null): ?float
    {
        $year = $year ?? now()->year;

        $wins = Bet::whereYear('betting_date', $year)
            ->where('status', 'Won')
            ->count();

        $losses = Bet::whereYear('betting_date', $year)
            ->where('status', 'Lost')
            ->count();

        $total = $wins + $losses;

        if ($total === 0) {
            return null;
        }

        return round($wins / $total, 2); // e.g., 0.75 means 75% win rate
    }

    /**
     * Get win/loss ratio for a specific month and year.
     *
     * @param int $year
     * @param int $month
     * @return float|null  // Returns ratio as a decimal (e.g., 0.75 for 75% win rate), or null if no bets
     */
    public function getWinLossRatioByMonth($year, $month): ?float
    {
        $wins = Bet::whereYear('betting_date', $year)->whereMonth('betting_date', $month)->where('status', 'Won')->count();
        $losses = Bet::whereYear('betting_date', $year)->whereMonth('betting_date', $month)->where('status', 'Lost')->count();
        $total = $wins + $losses;
        return $total > 0 ? round($wins / $total * 100, 2) : null;
    }

    /**
     * Get profit and ROI by membership level.
     *
     * @return array
     */
    public function getProfitAndROIByLevel(?int $year = null): array
    {
        $levelsOrder = ['Bronze', 'Silver', 'Gold', 'Platinum'];

        $query = Bet::query();

        if ($year) {
            $query->whereYear('betting_date', $year);
        } else {
            $query->whereYear('betting_date', now()->year);
        }

        $data = $query->selectRaw('membership as level, SUM(profit_amount) as profit, SUM(wager_amount) as total_wager, SUM(winning_amount) as total_winning')
            ->groupBy('membership')
            ->get()
            ->map(fn($item) => [
                'level' => ucfirst(strtolower($item->level)),
                'profit' => round($item->total_winning, 2),
                'roi' => $item->total_wager > 0 ? round(($item->total_winning / $item->total_wager)*100, 2) : 0,
            ])
            ->toArray();
        // Remove duplicates by level (keep the first occurrence)
        $data = collect($data)->unique('level')->values()->all();

        // Sort by custom order
        usort($data, function ($a, $b) use ($levelsOrder) {
            $posA = array_search($a['level'], $levelsOrder);
            $posB = array_search($b['level'], $levelsOrder);
            return $posA <=> $posB;
        });

        return $data;
    }
    /**
     * Get win/loss ratio for a specific year as a percentage.
     *
     * @param int $year
     * @return float|null  // Returns ratio as a percentage (e.g., 75 for 75% win rate), or null if no bets
     */
    public function getWinLossRatioByYear(int $year): ?float
    {
        $wins = Bet::whereYear('betting_date', $year)->where('status', 'Won')->count();
        $losses = Bet::whereYear('betting_date', $year)->where('status', 'Lost')->count();
        $total = $wins + $losses;
        return $total > 0 ? round($wins / $total * 100, 2) : null;
    }

    /**
     * Get total profit for a specific month and year.
     *
     * @param int $year
     * @param int $month
     * @return float
     */
    public function getProfitByMonth(int $year, int $month): float
    {
        return (float) Bet::whereYear('betting_date', $year)
            ->whereMonth('betting_date', $month)
            ->sum('winning_amount');
    }

    /**
     * Get average ROI for a specific month and year.
     *
     * @param int $year
     * @param int $month
     * @return float
     */
    public function getROIByMonth(int $year, int $month): float
    {
        $totalProfit = Bet::whereYear('betting_date', $year)
            ->whereMonth('betting_date', $month)
            ->sum('winning_amount');
        $totalWager = Bet::whereYear('betting_date', $year)
            ->whereMonth('betting_date', $month)
            ->sum('wager_amount');
        $roi = $totalWager > 0 ? ($totalProfit / $totalWager) * 100 : 0;
        //dd($totalProfit, $totalWager, $roi);
        return round($roi ?? 0, 2);
    }

    /**
     * Get all bets for export.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllBetsForExport()
    {
        return \App\Models\Bet::all();
    }

    /**
     * Get profit and ROI by year for all years.
     *
     * @return array
     */
    public function getProfitAndROIByYear(): array
    {
        return \App\Models\Bet::selectRaw('YEAR(betting_date) as year, SUM(profit_amount) as profit, SUM(winning_amount) as winnings, SUM(wager_amount) as wagers, AVG(roi) as roi')
            ->groupByRaw('YEAR(betting_date)')
            ->orderBy('year')
            ->get()
            ->map(function ($item) {
                return [
                    'year' => $item->year,
                    'profit' => round($item->profit, 2),
                    'roi' => round($item->winnings/$item->wagers, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get profit and ROI by month for all months (across all years).
     *
     * @return array
     */
    public function getProfitAndROIByMonth(): array
    {
        return \App\Models\Bet::selectRaw("DATE_FORMAT(betting_date, '%M %Y') as month, SUM(profit_amount) as profit, AVG(roi) as roi")
            ->groupByRaw("DATE_FORMAT(betting_date, '%M %Y')")
            ->orderByRaw("MIN(betting_date)")
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'profit' => round($item->profit, 2),
                    'roi' => round($item->roi, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Calculate the payout for an each-way bet.
     *
     * @param float $stake The total stake (will be split in half for win/place)
     * @param float $odds The win odds (e.g., 100 for 100-1)
     * @param float $placeFraction The fraction for the place bet (e.g., 0.25 for 1/4 odds)
     * @param bool $won True if the selection won outright
     * @param bool $placed True if the selection placed (but didn't win)
     * @return array ['win' => float, 'place' => float, 'total' => float]
     */
    public function calculateEachWayPayout(
        float $stake,
        float $odds,
        float $placeFraction,
        bool $won,
        bool $placed
    ): array {
        $unitStake = $stake / 2;
        $winPayout = 0;
        $placePayout = 0;

        if ($won) {
            // Win: full odds + stake
            $winPayout = $unitStake * $odds + $unitStake;
            // Place: place odds + stake
            $placePayout = $unitStake * ($odds * $placeFraction) + $unitStake;
        } elseif ($placed) {
            // Only place: place odds + stake
            $placePayout = $unitStake * ($odds * $placeFraction) + $unitStake;
        }

        return [
            'win' => $winPayout,
            'place' => $placePayout,
            'total' => $winPayout + $placePayout,
        ];
    }
}
