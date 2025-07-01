<?php

namespace App\Http\Controllers;

use App\Services\BetService;
use App\Services\SimpleCacheService;
use App\Models\Testimonial;
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
            'freeBets' => $this->betService->getTodaysBets(),
            'winRatio' => $this->betService->getWinLossRatio(),
            'thisYearWinLoss' => $this->betService->getWinLossRatioByYear($thisYear),
            'lastYearWinLoss' => $this->betService->getWinLossRatioByYear($lastYear),
            'thisMonthProfit' => $this->betService->getProfitByMonth($thisYear, $thisMonth),
            'thisMonthROI' => $this->betService->getROIByMonth($thisYear, $thisMonth),
            'thisMonthWinLoss' => $this->betService->getWinLossRatioByMonth($thisYear, $thisMonth),
            'lastMonthProfit' => $this->betService->getProfitByMonth($lastMonthYear, $lastMonthNum),
            'lastMonthROI' => $this->betService->getROIByMonth($lastMonthYear, $lastMonthNum),
            'lastMonthWinLoss' => $this->betService->getWinLossRatioByMonth($lastMonthYear, $lastMonthNum),
            'monthlyProfit' => $this->betService->getAverageMonthlyProfit(),
            'testimonials' => SimpleCacheService::rememberQuery(
                SimpleCacheService::KEY_TESTIMONIALS,
                SimpleCacheService::TTL_LONG,
                fn() => Testimonial::forDisplay()->limit(3)->get()
            ),
        ]);
    }
}