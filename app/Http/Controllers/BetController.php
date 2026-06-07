<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use App\Services\BetService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BetController extends Controller
{
    /**
     * The BetService instance.
     *
     * @var \App\Services\BetService
     */
    protected $betService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BetService $betService)
    {
        $this->betService = $betService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        if (request()->user()->cannot('viewAny', Bet::class)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $bets = $this->betService->getAllBets();

        return response()->json($bets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->user()->cannot('create', Bet::class)) {
            session()->flash('error', 'You are not authorized to create a bet.');

            return redirect()->back();
        }

        $bet = $this->betService->createBetFromRequest($request);

        session()->flash('success', 'Bet created successfully!');

        return redirect()->route('dashboard');
    }

    /**
     * Display the specified resource.
     */
    public function show(Bet $bet)
    {
        //
        if (request()->user()->cannot('view', $bet)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($bet);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bet $bet)
    {
        if ($request->user()->cannot('update', $bet)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $updatedBet = $this->betService->updateBetAndCalculateROI($bet, $request);

        return response()->json($updatedBet);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bet $bet)
    {
        if (request()->user()->cannot('delete', $bet)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->betService->deleteBet($bet);

        return response()->json(null, 204);
    }

    /**
     * Get the bets for a specific sport.
     *
     * @param  string  $sport
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBetsBySport($sport)
    {
        if (request()->user()->cannot('viewAny', Bet::class)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $bets = $this->betService->getBetsBySport($sport);

        return response()->json($bets);
    }

    /**
     * Get all bets.
     *
     * @return \Inertia\Response
     */
    public function getAllBets()
    {
        // Ensure the user is an admin
        if (request()->user()->cannot('viewAny', Bet::class)) {
            abort(403, 'Unauthorized');
        }

        // Fetch all bets using the BetService
        $bets = $this->betService->getAllBets();

        // Return an Inertia response
        return Inertia::render('Dashboard', [
            'bets' => $bets,
        ]);
    }

    /**
     * Show a single bet pick.
     */
    public function showPick($id)
    {
        $bet = Bet::findOrFail($id);

        return Inertia::render('BetPickShow', [
            'bet' => $bet,
        ]);
    }

    /**
     * Show today's bets.
     */
    public function todaysBets()
    {
        // Get all active bets (betting_date <= now, game_date >= now)
        $allBets = $this->betService->getTodaysBets();

        // Calculate total bets per sport from active bets
        $totalBetsPerSport = $allBets->groupBy(function ($bet) {
            return $bet->sports ?? $bet->sport ?? 'Football';
        })->map->count();

        // For unauthenticated users, show limited free picks + teaser count
        $user = auth()->user();
        $premiumPicksCount = 0;

        if (! $user) {
            // Get sport preferences
            $sportPreferences = \App\Models\SportPreference::active()
                ->orderBy('priority', 'asc')
                ->pluck('sport_name')
                ->toArray();

            // Filter free/bronze/silver bets
            $freeTierBets = $allBets->filter(function ($bet) {
                $tier = strtolower($bet->membership ?? $bet->level ?? '');

                return in_array($tier, ['free', 'bronze', 'silver']);
            });

            // Sort by sport preference and limit to 2
            $freeBets = $freeTierBets->sortBy(function ($bet) use ($sportPreferences) {
                $sport = $bet->sports ?? $bet->sport ?? '';
                $index = array_search($sport, $sportPreferences);

                return $index === false ? 999 : $index;
            })->take(2)->values();

            // Count premium picks for teaser card (don't send the actual bets)
            $premiumPicksCount = $allBets->filter(function ($bet) {
                $tier = strtolower($bet->membership ?? $bet->level ?? '');

                return in_array($tier, ['gold', 'platinum']);
            })->count();
        } else {
            $freeBets = $allBets;
        }

        // Get partner offers for sidebar
        $partnerOffers = \App\Models\PartnerOffer::forPicks()
            ->with('discountCode:id,code')
            ->take(3)
            ->get(['id', 'name', 'slug', 'logo', 'offer_text', 'external_url', 'internal_url', 'discount_code_id', 'type', 'badge_text'])
            ->map(function ($offer) {
                $offer->click_url = $offer->getClickUrl();

                return $offer;
            });

        return Inertia::render('TodaysBets', [
            'roiData' => $this->betService->getTotalROIBySubscriptionLevel(),
            'freeBets' => $freeBets,
            'totalBetsPerSport' => $totalBetsPerSport,
            'sportPreferences' => \App\Models\SportPreference::active()->get(),
            'availableGameDates' => $this->betService->getAvailableGameDates(),
            'premiumPicksCount' => $premiumPicksCount,
            'partnerOffers' => $partnerOffers,
        ]);
    }

    /**
     * Show betting results.
     */
    public function bettingResults()
    {
        $thisYear = now()->year;
        $lastYear = $thisYear - 1; // Always the full previous year
        $thisMonth = now()->month;
        $lastMonth = now()->copy()->subMonthNoOverflow();
        $lastMonthYear = $lastMonth->year;
        $lastMonthNum = $lastMonth->month;
        $profitByYear = $this->betService->getProfitByYear();
        $roiByYear = $this->betService->getROIByYear();
        $thisMonthROI = $this->betService->getROIByMonth($thisYear, $thisMonth);
        $lastMonthROI = $this->betService->getROIByMonth($lastMonthYear, $lastMonthNum);

        // Get profit by year data from service
        $profitByYearData = $this->betService->getProfitAndROIByYear();

        // Add static values for 2022, 2023, 2024, and 2025
        $staticData = [
            2022 => ['profit' => 15769, 'roi' => 19],
            2023 => ['profit' => 21678, 'roi' => 16],
            2024 => ['profit' => 13509, 'roi' => 15],
            2025 => ['profit' => 58559, 'roi' => 52],
        ];

        // Merge static values with calculated data
        foreach ($staticData as $year => $data) {
            $found = false;
            foreach ($profitByYearData as &$yearData) {
                if ($yearData['year'] == $year) {
                    $yearData['profit'] = $data['profit'];
                    $yearData['roi'] = $data['roi'];
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $profitByYearData[] = [
                    'year' => $year,
                    'profit' => $data['profit'],
                    'roi' => $data['roi'],
                    'total_bets' => 0,
                    'wins' => 0,
                    'win_rate' => 0,
                ];
            }
        }

        return Inertia::render('BettingResults', [
            'roiData' => $this->betService->getTotalROIBySubscriptionLevel($thisYear),
            'sportProfitRoiData' => $this->betService->getProfitAndROIBySport($thisYear),
            'levelProfitRoiData' => $this->betService->getProfitAndROIByLevel($thisYear),
            'thisYear' => $thisYear,
            'lastYear' => $lastYear,
            'thisYearProfit' => $profitByYear[$thisYear] ?? 0,
            'lastYearProfit' => $profitByYear[$lastYear] ?? 0,
            'thisYearROI' => $roiByYear[$thisYear] ?? 0,
            'lastYearROI' => $roiByYear[$lastYear] ?? 0,
            'thisYearWinLoss' => $this->betService->getWinLossRatioByYear($thisYear)['win_rate'] ?? 0,
            'lastYearWinLoss' => $this->betService->getWinLossRatioByYear($lastYear)['win_rate'] ?? 0,
            'thisMonthProfit' => $this->betService->getProfitByMonth($thisYear, $thisMonth),
            'thisMonthROI' => $thisMonthROI,
            'thisMonthWinLoss' => $this->betService->getWinLossRatioByMonth($thisYear, $thisMonth)['win_rate'] ?? 0,
            'lastMonthProfit' => $this->betService->getProfitByMonth($lastMonthYear, $lastMonthNum),
            'lastMonthROI' => $lastMonthROI,
            'lastMonthWinLoss' => $this->betService->getWinLossRatioByMonth($lastMonthYear, $lastMonthNum)['win_rate'] ?? 0,
            'monthlyProfit' => $this->betService->getAverageMonthlyProfit(),
            'profitByYearData' => $profitByYearData,
            'profitByMonthData' => $this->betService->getProfitAndROIByMonth(),
            'levelProfitRoiDataLastYear' => $this->betService->getProfitAndROIByLevel($lastYear),
            'roiDataLastYear' => $this->betService->getTotalROIBySubscriptionLevel($lastYear),
            'sportProfitRoiDataLastYear' => $this->betService->getProfitAndROIBySport($lastYear),
        ]);
    }

    /**
     * Show authenticated bet.
     */
    public function authenticatedShow(Request $request, Bet $bet)
    {
        if ($request->user()->can('view', $bet) === false) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('BetPickShow', [
            'bet' => $bet,
        ]);
    }

    /**
     * Get ticker bets - last 10 bets with preferred sports prioritized.
     */
    public function getTickerBets()
    {
        $bets = $this->betService->getTickerBets();

        return response()->json([
            'bets' => $bets,
        ]);
    }
}
