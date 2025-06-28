<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CareerApplicationController;
use App\Http\Controllers\Admin\BetImportWizardController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\LandingPageController;
use App\Http\Controllers\Admin\AdminToolsController;
use App\Http\Controllers\BettingEducationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BetController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageShowController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Middleware\AdminMiddleware;

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
Route::get('/subscription-checkout', [RegisteredUserController::class, 'newSubscription'])
    ->name('subscription.checkout')
    ->middleware(['auth', 'verified']);

Route::post('/careers/apply', [CareerApplicationController::class, 'submit'])->name('careers.apply');


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
// Admin tools routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/notify-all', [AdminToolsController::class, 'notifyAll'])->name('notify-all');
    Route::get('/bets/export-csv', [AdminToolsController::class, 'exportBets'])->name('bets.export');
});
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin/pages')->name('admin.pages.')->group(function () {
    Route::get('/', [PageController::class, 'index'])->name('index');
    Route::get('/create', [PageController::class, 'create'])->name('create');
    Route::post('/', [PageController::class, 'store'])->name('store');
    Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
    Route::put('/{page}', [PageController::class, 'update'])->name('update');
    Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
});
Route::get('/pages/{slug}', [PageShowController::class, 'showPage'])->name('pages.show');

Route::middleware(['auth', AdminMiddleware::class])->prefix('admin/customers')->name('admin.customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::put('/{user}', [CustomerController::class, 'update'])->name('update');
});

Route::middleware(['auth', AdminMiddleware::class])->prefix('admin/admins')->name('admin.admins.')->group(function () {
    Route::get('/', [AdminUserController::class, 'index'])->name('index');
    Route::post('/add', [AdminUserController::class, 'add'])->name('add');
    Route::post('/remove', [AdminUserController::class, 'remove'])->name('remove');
});

Route::middleware(['auth', AdminMiddleware::class])->prefix('admin/landing-pages')->name('admin.landing-pages.')->group(function () {
    Route::get('/', [LandingPageController::class, 'index'])->name('index');
    Route::get('/create', [LandingPageController::class, 'create'])->name('create');
    Route::post('/', [LandingPageController::class, 'store'])->name('store');
    Route::get('/{page}/edit', [LandingPageController::class, 'edit'])->name('edit');
    Route::put('/{page}', [LandingPageController::class, 'update'])->name('update');
    Route::delete('/{page}', [LandingPageController::class, 'destroy'])->name('destroy');
});
Route::get('/landing/{slug}', [PageShowController::class, 'showLandingPage'])->name('landing.show');







