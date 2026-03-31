<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
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

        // Get golf statistics for 2026 (only settled bets for ROI calculation)
        $golf2026Bets = \App\Models\Bet::whereYear('game_date', 2026)
            ->where(function ($query) {
                $query->where('sports', 'LIKE', '%golf%')
                    ->orWhere('sport', 'LIKE', '%golf%');
            })
            ->whereIn('status', ['won', 'loss', 'push', 'void', 'placed']) // Only settled bets
            ->get();

        $golfWinners2026 = $golf2026Bets->where('status', 'won')->count();

        // Calculate golf ROI for 2026 (only from settled bets)
        $totalWager = $golf2026Bets->sum('wager_amount');
        $totalProfit = $golf2026Bets->sum('profit_amount');
        $golfROI2026 = $totalWager > 0 ? round(($totalProfit / $totalWager) * 100) : 0;

        // Get active bets (betting_date <= now, game_date >= now)
        $allBets = $this->betService->getTodaysBets()->map(function ($bet) {
            // Add team logos from relationships if not already set
            if (empty($bet->team_one_logo) && $bet->teamOne && $bet->teamOne->logo_url) {
                $bet->team_one_logo = \Storage::url($bet->teamOne->logo_url);
            }
            if (empty($bet->team_two_logo) && $bet->teamTwo && $bet->teamTwo->logo_url) {
                $bet->team_two_logo = \Storage::url($bet->teamTwo->logo_url);
            }

            return $bet;
        });

        // Calculate total bets per sport from active bets
        $totalBetsPerSport = $allBets->groupBy(function ($bet) {
            return $bet->sports ?? $bet->sport ?? 'Football';
        })->map->count();

        // For unauthenticated users, limit free bets to 2 from preferred sports (teaser)
        $user = auth()->user();
        if (! $user) {
            // Get sport preferences
            $sportPreferences = \App\Models\SportPreference::active()
                ->orderBy('priority', 'asc')
                ->pluck('sport_name')
                ->toArray();

            // Filter and limit free/bronze/silver bets
            $freeTierBets = $allBets->filter(function ($bet) {
                $tier = strtolower($bet->membership ?? $bet->level ?? '');

                return in_array($tier, ['free', 'bronze', 'silver']);
            });

            // Sort by sport preference and limit to 2
            $limitedBets = $freeTierBets->sortBy(function ($bet) use ($sportPreferences) {
                $sport = $bet->sports ?? $bet->sport ?? '';
                $index = array_search($sport, $sportPreferences);

                return $index === false ? 999 : $index;
            })->take(2);

            // Include premium bets for display (they'll be covered)
            $premiumBets = $allBets->filter(function ($bet) {
                $tier = strtolower($bet->membership ?? $bet->level ?? '');

                return in_array($tier, ['gold', 'platinum']);
            });

            $freeBets = $limitedBets->merge($premiumBets);
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
            'golfWinners2026' => $golfWinners2026,
            'golfROI2026' => $golfROI2026,
            'testimonials' => SimpleCacheService::rememberQuery(
                SimpleCacheService::KEY_TESTIMONIALS,
                SimpleCacheService::TTL_LONG,
                fn () => Testimonial::forDisplay()->limit(3)->get()
            ),
            'sportPreferences' => \App\Models\SportPreference::active()->get(),
            'availableGameDates' => $this->betService->getAvailableGameDates(),
            'enableDraftkingsCta' => SiteSetting::get('enable_draftkings_cta', true),
        ]);
    }
}
