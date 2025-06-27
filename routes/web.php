<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\CareerApplicationController;
use App\Http\Controllers\Admin\BetImportController;
use App\Http\Controllers\Admin\BetImportWizardController;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Models\Bet;
use App\Http\Controllers\Admin\PageController;
use App\Models\Page;
use App\Models\LandingPage;

use App\Http\Controllers\Admin\CustomerController;

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\BettingEducationController;
use App\Http\Controllers\Admin\LandingPageController;

Route::get('/', function (\App\Services\BetService $betService) {
   $thisYear = now()->year;
    $lastYear = now()->subYear()->year;
    $thisMonth = now()->month;
    $lastMonth = now()->subMonthNoOverflow();
    $lastMonthYear = $lastMonth->year;
    $lastMonthNum = $lastMonth->month;
    $profitByYear = $betService->getProfitByYear();
    $roiByYear = $betService->getROIByYear();
    return inertia('Welcome', [
        'roiData' => $betService->getTotalROIBySubscriptionLevel(),
         'levelProfitRoiData' => $betService->getProfitAndROIByLevel(),
        'sportProfitRoiData' => $betService->getProfitAndROIBySport(),
        'thisYear' => $thisYear,
        'lastYear' => $lastYear,
        'thisYearProfit' => $profitByYear[$thisYear] ?? 0,
        'lastYearProfit' => $profitByYear[$lastYear] ?? 0,
        'thisYearROI' => $roiByYear[$thisYear] ?? 0,
        'lastYearROI' => $roiByYear[$lastYear] ?? 0,
        'monthlyProfit' => $betService->getAverageMonthlyProfit(),
        'freeBets' => $betService->getTodaysBets(),
        'winRatio' => $betService->getWinLossRatio(),
        'thisYearWinLoss' => $betService->getWinLossRatioByYear($thisYear),
        'lastYearWinLoss' => $betService->getWinLossRatioByYear($lastYear),
        'thisMonthProfit' => $betService->getProfitByMonth($thisYear, $thisMonth),
        'thisMonthROI' => $betService->getROIByMonth($thisYear, $thisMonth),
        'thisMonthWinLoss' => $betService->getWinLossRatioByMonth($thisYear, $thisMonth),
        'lastMonthProfit' => $betService->getProfitByMonth($lastMonthYear, $lastMonthNum),
        'lastMonthROI' => $betService->getROIByMonth($lastMonthYear, $lastMonthNum),
        'lastMonthWinLoss' => $betService->getWinLossRatioByMonth($lastMonthYear, $lastMonthNum),
        'monthlyProfit' => $betService->getAverageMonthlyProfit(),
    ]);
})->name('home');
Route::get('/pick/{id}', function ($id) {
    $bet = Bet::findOrFail($id);
    return inertia('BetPickShow', [
        'bet' => $bet,
    ]);
})->name('pick.show');
Route::get('/odds', function() {
    return inertia('Odds');
})->name('odds');
Route::get('/futures', function() {
    return inertia('Futures');
})->name('futures');
Route::get('/todays-tips', function (\App\Services\BetService $betService) {
   
    return inertia('TodaysBets', [
        'roiData' => $betService->getTotalROIBySubscriptionLevel(),
        'freeBets' => $betService->getTodaysBets()
    ]);
})->name('todays-bets');
Route::get('/buy-our-picks', function (\App\Services\BetService $betService) {
    return inertia('BuyOurPicks', );
})->name('buy-our-picks');
Route::get('/betting-results', function (\App\Services\BetService $betService) {
    $thisYear = now()->year;
    $lastYear = now()->subYear()->year;
    $thisMonth = now()->month;
    $lastMonth = now()->copy()->subMonthNoOverflow();
    $lastMonthYear = $lastMonth->year;
    $lastMonthNum = $lastMonth->month;
    //dd($lastMonth, $lastMonthYear, $lastMonthNum);
    $profitByYear = $betService->getProfitByYear();
    $roiByYear = $betService->getROIByYear();
    $thisMonthROI = $betService->getROIByMonth($thisYear, $thisMonth);
    $lastMonthROI = $betService->getROIByMonth($lastMonthYear, $lastMonthNum);
    //dd($lastMonthROI, $thisMonthROI);
    return inertia('BettingResults', [
        'roiData' => $betService->getTotalROIBySubscriptionLevel(),
        'sportProfitRoiData' => $betService->getProfitAndROIBySport(),
        'levelProfitRoiData' => $betService->getProfitAndROIByLevel(),
        'thisYear' => $thisYear,
        'lastYear' => $lastYear,
        'thisYearProfit' => $profitByYear[$thisYear] ?? 0,
        'lastYearProfit' => $profitByYear[$lastYear] ?? 0,
        'thisYearROI' => $roiByYear[$thisYear] ?? 0,
        'lastYearROI' => $roiByYear[$lastYear] ?? 0,
        'thisYearWinLoss' => $betService->getWinLossRatioByYear($thisYear),
        'lastYearWinLoss' => $betService->getWinLossRatioByYear($lastYear),
        'thisMonthProfit' => $betService->getProfitByMonth($thisYear, $thisMonth),
        'thisMonthROI' => $thisMonthROI,
        'thisMonthWinLoss' => $betService->getWinLossRatioByMonth($thisYear, $thisMonth),
        'lastMonthProfit' => $betService->getProfitByMonth($lastMonthYear, $lastMonthNum),
        'lastMonthROI' => $lastMonthROI,
        'lastMonthWinLoss' => $betService->getWinLossRatioByMonth($lastMonthYear, $lastMonthNum),
        'monthlyProfit' => $betService->getAverageMonthlyProfit(),
        'profitByYearData' => $betService->getProfitAndROIByYear(),
        'profitByMonthData' => $betService->getProfitAndROIByMonth(),
        'levelProfitRoiDataLastYear' => $betService->getProfitAndROIByLevel($lastYear),
        'roiDataLastYear' => $betService->getTotalROIBySubscriptionLevel($lastYear),
        'sportProfitRoiDataLastYear' => $betService->getProfitAndROIBySport($lastYear),
    ]);
})->name('betting-results');
Route::get('/betting-education', BettingEducationController::class)->name('betting-tips');
Route::get('/partners-offers', function () {
    return inertia('PartnerOffers');
})->name('partner-offers');
Route::get('/careers-jobs', function () {
    return inertia('CareersJobs');
})->name('careers-jobs');
Route::get('/about-us', function () {
    return inertia('AboutUs');
})->name('about-us');

Route::get('dashboard', function (\App\Services\BetService $betService) {
    return Inertia::render('Dashboard', [
        'subscriptions' => Auth::user()->subscriptions,
        'roiData' => $betService->getTotalROIBySubscriptionLevel(),
        'sportProfitRoiData' => $betService->getProfitAndROIBySport(),
        
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/bet/{bet}', function (Request $request, Bet $bet) {
    if($request->user()->can('view', $bet) === false) {
        abort(403, 'Unauthorized');
    }
    return inertia('BetPickShow', [
        'bet' => $bet
    ]);
})->middleware(['auth', 'verified'])->name('bet.show');
Route::get('/subscription-checkout', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'newSubscription'])
    ->name('subscription.checkout')
    ->middleware(['auth', 'verified']);

Route::post('/careers/apply', [CareerApplicationController::class, 'submit'])->name('careers.apply');

// Legacy bet import route
Route::post('/admin/bets/import-csv', [BetImportController::class, 'importCsv'])->middleware('auth');

// New bet import wizard routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin/bets/import')->name('admin.bets.import.')->group(function () {
    Route::get('/', [BetImportWizardController::class, 'index'])->name('index');
    Route::post('/upload', [BetImportWizardController::class, 'upload'])->name('upload');
    Route::post('/validate', [BetImportWizardController::class, 'validate'])->name('validate');
    Route::post('/process', [BetImportWizardController::class, 'import'])->name('process');
    Route::get('/progress', [BetImportWizardController::class, 'progress'])->name('progress');
    Route::get('/template', [BetImportWizardController::class, 'downloadTemplate'])->name('template');
    Route::get('/error-report', [BetImportWizardController::class, 'downloadErrorReport'])->name('error-report');
});
Route::post('/admin/notify-all', function (Request $request) {
    if (!auth()->check() || !auth()->user()->hasRole('admin')) {
        abort(403, 'Unauthorized');
    }
    $request->validate([
        'title' => 'required|string|max:255',
        'body' => 'required|string|max:1000',
    ]);
    $users = User::all();
    Notification::send($users, new \App\Notifications\GenericAdminNotification($request->title, $request->body));
    return response()->json(['success' => true]);
})->middleware(['auth', 'verified']);
Route::get('/admin/bets/export-csv', function (\App\Services\BetService $betService) {
    if (!auth()->check() || !auth()->user()->hasRole('admin')) {
        abort(403, 'Unauthorized');
    }
    $bets = $betService->getAllBetsForExport();

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="bets_export.csv"',
    ];

    $callback = function() use ($bets) {
        $handle = fopen('php://output', 'w');
        // CSV header (adjust to match your import format)
        fputcsv($handle, [
            'id', 'sports', 'league', 'matches', 'markets', 'team_one', 'team_two',
            'team_one_logo', 'team_two_logo', 'wager_amount', 'winning_amount', 'profit_amount', 'roi', 'betting_date',
            'tips', 'wager_odds', 'status', 'membership', 'referrer'
        ]);
        foreach ($bets as $bet) {
            fputcsv($handle, [
                $bet->id,
                $bet->sports,
                $bet->league,
                $bet->matches,
                $bet->markets,
                $bet->team_one,
                $bet->team_two,
                $bet->team_one_logo,
                $bet->team_two_logo,
                $bet->wager_amount,
                $bet->winning_amount,
                $bet->profit_amount,
                $bet->roi,
                $bet->betting_date,
                $bet->tips,
                $bet->wager_odds,
                $bet->status,
                $bet->membership,
                $bet->referrer,
            ]);
        }
        fclose($handle);
    };

    return Response::stream($callback, 200, $headers);
})->middleware(['auth', 'verified']);
Route::middleware(['auth',AdminMiddleware::class])->prefix('admin/pages')->name('admin.pages.')->group(function () {
    Route::get('/', [PageController::class, 'index'])->name('index');
    Route::get('/create', [PageController::class, 'create'])->name('create');
    Route::post('/', [PageController::class, 'store'])->name('store');
    Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
    Route::put('/{page}', [PageController::class, 'update'])->name('update');
    Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
});
Route::get('/pages/{slug}', function ($slug) {
    $page = Page::where('slug', $slug)->where('published', true)->firstOrFail();
    return inertia('PageShow', ['page' => $page]);
})->name('pages.show');

Route::middleware(['auth',AdminMiddleware::class])->prefix('admin/customers')->name('admin.customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::put('/{user}', [CustomerController::class, 'update'])->name('update');
});

Route::middleware(['auth',AdminMiddleware::class])->prefix('admin/admins')->name('admin.admins.')->group(function () {
    Route::get('/', [AdminUserController::class, 'index'])->name('index');
    Route::post('/add', [AdminUserController::class, 'add'])->name('add');
    Route::post('/remove', [AdminUserController::class, 'remove'])->name('remove');
});

Route::middleware(['auth',AdminMiddleware::class])->prefix('admin/landing-pages')->name('admin.landing-pages.')->group(function () {
    Route::get('/', [LandingPageController::class, 'index'])->name('index');
    Route::get('/create', [LandingPageController::class, 'create'])->name('create');
    Route::post('/', [LandingPageController::class, 'store'])->name('store');
    Route::get('/{page}/edit', [LandingPageController::class, 'edit'])->name('edit');
    Route::put('/{page}', [LandingPageController::class, 'update'])->name('update');
    Route::delete('/{page}', [LandingPageController::class, 'destroy'])->name('destroy');
});
Route::get('/landing/{slug}', function ($slug) {
    $page = LandingPage::where('slug', $slug)->where('published', true)->firstOrFail();
    return inertia('LandingPageShow', ['page' => $page]);
})->name('landing.show');
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/blog.php';







