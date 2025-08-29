<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use App\Services\BetService;
use App\Services\SimpleCacheService;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct(
        private BetService $betService
    ) {}

    public function index()
    {
        $thisYear = now()->year;
        $lastYear = now()->subYear()->year;
        $thisMonth = now()->month;
        $lastMonth = now()->subMonthNoOverflow();
        $lastMonthYear = $lastMonth->year;
        $lastMonthNum = $lastMonth->month;
        $profitByYear = $this->betService->getProfitByYear();
        $roiByYear = $this->betService->getROIByYear();

        // Get active bets (betting_date <= now, game_date >= now)
        $allBets = $this->betService->getTodaysBets();
        
        // Calculate total bets per sport from active bets
        $totalBetsPerSport = $allBets->groupBy(function ($bet) {
            return $bet->sports ?? $bet->sport ?? 'Football';
        })->map->count();
        
        // For unauthenticated users, limit bronze bets to 2 from preferred sports
        $user = auth()->user();
        if (!$user) {
            // Get sport preferences
            $sportPreferences = \App\Models\SportPreference::active()
                ->orderBy('priority', 'asc')
                ->pluck('sport_name')
                ->toArray();
            
            // Filter and limit bronze bets
            $bronzeBets = $allBets->filter(function ($bet) {
                return strtolower($bet->membership ?? $bet->level ?? '') === 'bronze';
            });
            
            // Sort by sport preference and limit to 2
            $limitedBets = $bronzeBets->sortBy(function ($bet) use ($sportPreferences) {
                $sport = $bet->sports ?? $bet->sport ?? '';
                $index = array_search($sport, $sportPreferences);
                return $index === false ? 999 : $index;
            })->take(2);
            
            // Include non-bronze bets for display (they'll be covered)
            $nonBronzeBets = $allBets->filter(function ($bet) {
                return strtolower($bet->membership ?? $bet->level ?? '') !== 'bronze';
            });
            
            $freeBets = $limitedBets->merge($nonBronzeBets);
        } else {
            $freeBets = $allBets;
        }

        return Inertia::render('Welcome', [
            'roiData' => $this->betService->getTotalROIBySubscriptionLevel(),
            'levelProfitRoiData' => $this->betService->getProfitAndROIByLevel(),
            'sportProfitRoiData' => $this->betService->getProfitAndROIBySport(),
            'thisYear' => $thisYear,
            'lastYear' => $lastYear,
            'thisYearProfit' => $profitByYear[$thisYear] ?? 0,
            'lastYearProfit' => $profitByYear[$lastYear] ?? 0,
            'thisYearROI' => $roiByYear[$thisYear] ?? 0,
            'lastYearROI' => $roiByYear[$lastYear] ?? 0,
            'monthlyProfit' => $this->betService->getAverageMonthlyProfit(),
            'freeBets' => $freeBets,
            'totalBetsPerSport' => $totalBetsPerSport,
            'winRatio' => $this->betService->getWinLossRatio()['win_rate'] ?? 0,
            'thisYearWinLoss' => $this->betService->getWinLossRatioByYear($thisYear)['win_rate'] ?? 0,
            'lastYearWinLoss' => $this->betService->getWinLossRatioByYear($lastYear)['win_rate'] ?? 0,
            'thisMonthProfit' => $this->betService->getProfitByMonth($thisYear, $thisMonth),
            'thisMonthROI' => $this->betService->getROIByMonth($thisYear, $thisMonth),
            'thisMonthWinLoss' => $this->betService->getWinLossRatioByMonth($thisYear, $thisMonth)['win_rate'] ?? 0,
            'lastMonthProfit' => $this->betService->getProfitByMonth($lastMonthYear, $lastMonthNum),
            'lastMonthROI' => $this->betService->getROIByMonth($lastMonthYear, $lastMonthNum),
            'lastMonthWinLoss' => $this->betService->getWinLossRatioByMonth($lastMonthYear, $lastMonthNum)['win_rate'] ?? 0,
            'monthlyProfit' => $this->betService->getAverageMonthlyProfit(),
            'testimonials' => SimpleCacheService::rememberQuery(
                SimpleCacheService::KEY_TESTIMONIALS,
                SimpleCacheService::TTL_LONG,
                fn () => Testimonial::forDisplay()->limit(3)->get()
            ),
            'sportPreferences' => \App\Models\SportPreference::active()->get(),
            'availableGameDates' => $this->betService->getAvailableGameDates(),
        ]);
    }
}
