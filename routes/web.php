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
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BetController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminToolsController;
use App\Http\Controllers\PageShowController;
use App\Http\Controllers\BlogController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/pick/{id}', [BetController::class, 'showPick'])->name('pick.show');
Route::get('/odds', [StaticPageController::class, 'odds'])->name('odds');
Route::get('/futures', [StaticPageController::class, 'futures'])->name('futures');
Route::get('/todays-tips', [BetController::class, 'todaysBets'])->name('todays-bets');
Route::get('/buy-our-picks', [StaticPageController::class, 'buyOurPicks'])->name('buy-our-picks');
Route::get('/betting-results', [BetController::class, 'bettingResults'])->name('betting-results');
Route::get('/betting-education', BettingEducationController::class)->name('betting-tips');
Route::get('/partners-offers', [StaticPageController::class, 'partnerOffers'])->name('partner-offers');
Route::get('/careers-jobs', [StaticPageController::class, 'careersJobs'])->name('careers-jobs');
Route::get('/about-us', [StaticPageController::class, 'aboutUs'])->name('about-us');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/bet/{bet}', [BetController::class, 'authenticatedShow'])->middleware(['auth', 'verified'])->name('bet.show');
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
Route::post('/admin/notify-all', [AdminToolsController::class, 'notifyAll'])->middleware(['auth', 'verified']);
Route::get('/admin/bets/export-csv', [AdminToolsController::class, 'exportBets'])->middleware(['auth', 'verified']);
Route::middleware(['auth',AdminMiddleware::class])->prefix('admin/pages')->name('admin.pages.')->group(function () {
    Route::get('/', [PageController::class, 'index'])->name('index');
    Route::get('/create', [PageController::class, 'create'])->name('create');
    Route::post('/', [PageController::class, 'store'])->name('store');
    Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
    Route::put('/{page}', [PageController::class, 'update'])->name('update');
    Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
});
Route::get('/pages/{slug}', [PageShowController::class, 'showPage'])->name('pages.show');

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
Route::get('/landing/{slug}', [PageShowController::class, 'showLandingPage'])->name('landing.show');







