<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CareerApplicationController;
use App\Http\Controllers\Admin\BetImportWizardController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\LandingPageController;
use App\Http\Controllers\Admin\AdminToolsController;
use App\Http\Controllers\Admin\StripeProductController;
use App\Http\Controllers\Admin\SubscriptionDashboardController;
use App\Http\Controllers\Admin\DiscountCodeController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BetManagementController;
use App\Http\Controllers\BettingEducationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BetController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\PageShowController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\SupportTicketController;
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

// Customer dashboard route
Route::get('dashboard', [CustomerDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin dashboard uses a different route (admin.dashboard)
Route::get('/bet/{bet}', [BetController::class, 'authenticatedShow'])->middleware(['auth', 'verified'])->name('bet.show');
Route::get('/subscription-checkout', [RegisteredUserController::class, 'newSubscription'])
    ->name('subscription.checkout')
    ->middleware(['auth', 'verified']);

Route::post('/careers/apply', [CareerApplicationController::class, 'submit'])->name('careers.apply');

// Support Ticket Routes
Route::middleware(['auth', 'verified'])->prefix('support')->name('support.')->group(function () {
    Route::get('/', [SupportTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [SupportTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [SupportTicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/replies', [SupportTicketController::class, 'reply'])->name('tickets.reply');
    Route::put('/tickets/{ticket}/close', [SupportTicketController::class, 'close'])->name('tickets.close');
    Route::put('/tickets/{ticket}/reopen', [SupportTicketController::class, 'reopen'])->name('tickets.reopen');
});

// Admin Authentication Routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'store']);
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');
});

// Admin Dashboard
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Support Ticket Management
    Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SupportTicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [\App\Http\Controllers\Admin\SupportTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [\App\Http\Controllers\Admin\SupportTicketController::class, 'reply'])->name('reply');
        Route::put('/{ticket}/status', [\App\Http\Controllers\Admin\SupportTicketController::class, 'updateStatus'])->name('update-status');
        Route::put('/{ticket}/priority', [\App\Http\Controllers\Admin\SupportTicketController::class, 'updatePriority'])->name('update-priority');
        Route::put('/{ticket}/assign', [\App\Http\Controllers\Admin\SupportTicketController::class, 'assign'])->name('assign');
        Route::post('/bulk-update', [\App\Http\Controllers\Admin\SupportTicketController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/api/statistics', [\App\Http\Controllers\Admin\SupportTicketController::class, 'statistics'])->name('statistics');
    });
    
    // Bet Management
    Route::resource('bets', BetManagementController::class);
    Route::post('bets/bulk-update-status', [BetManagementController::class, 'bulkUpdateStatus'])->name('bets.bulk-update-status');
    Route::get('bets/statistics', [BetManagementController::class, 'statistics'])->name('bets.statistics');
    
    // Game Management (TODO)
    // Route::resource('games', Admin\GameManagementController::class);
    
    // Team Management (TODO)
    // Route::resource('teams', Admin\TeamManagementController::class);
    
    // Sport Management (TODO)
    // Route::resource('sports', Admin\SportManagementController::class);
    
    // Operator Management (TODO)
    // Route::resource('operators', Admin\OperatorManagementController::class);
    
    // Notification Management (TODO)
    // Route::get('notifications/create', [Admin\NotificationController::class, 'create'])->name('notifications.create');
    // Route::post('notifications/send', [Admin\NotificationController::class, 'send'])->name('notifications.send');
    
    // Email Template Management (TODO)
    // Route::resource('email-templates', Admin\EmailTemplateController::class);
    
    // System Settings (TODO)
    // Route::get('settings', [Admin\SettingsController::class, 'index'])->name('settings.index');
    // Route::post('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
});


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
    Route::post('/{user}/impersonate', [\App\Http\Controllers\Admin\ImpersonationController::class, 'start'])->name('impersonate');
});

// Impersonation stop route (accessible when impersonating)
Route::get('/admin/impersonate/stop', [\App\Http\Controllers\Admin\ImpersonationController::class, 'stop'])
    ->name('admin.impersonate.stop')
    ->middleware('auth');

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

// Public Blog Routes
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

// Stripe Product Management Routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin/stripe-products')->name('admin.stripe-products.')->group(function () {
    Route::get('/', [StripeProductController::class, 'index'])->name('index');
    Route::post('/', [StripeProductController::class, 'store'])->name('store');
    Route::put('/{stripeProduct}', [StripeProductController::class, 'update'])->name('update');
    Route::delete('/{stripeProduct}', [StripeProductController::class, 'destroy'])->name('destroy');
    
    // Stripe API routes
    Route::get('/fetch-stripe-products', [StripeProductController::class, 'fetchFromStripe'])->name('fetch-stripe');
    Route::post('/fetch-prices', [StripeProductController::class, 'fetchPrices'])->name('fetch-prices');
    Route::post('/{stripeProduct}/connect', [StripeProductController::class, 'connectToStripe'])->name('connect');
    Route::post('/{stripeProduct}/create-in-stripe', [StripeProductController::class, 'createInStripe'])->name('create-in-stripe');
    Route::post('/{stripeProduct}/disconnect', [StripeProductController::class, 'disconnectFromStripe'])->name('disconnect');
});

// Subscription Dashboard Routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin/subscriptions')->name('admin.subscriptions.')->group(function () {
    Route::get('/', [SubscriptionDashboardController::class, 'index'])->name('index');
    Route::post('/export', [SubscriptionDashboardController::class, 'export'])->name('export');
    Route::post('/grant', [SubscriptionDashboardController::class, 'grantSubscription'])->name('grant');
    Route::post('/{user}/cancel', [SubscriptionDashboardController::class, 'cancelSubscription'])->name('cancel');
});

// Discount Code Management Routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin/discounts')->name('admin.discounts.')->group(function () {
    Route::get('/', [DiscountCodeController::class, 'index'])->name('index');
    Route::post('/', [DiscountCodeController::class, 'store'])->name('store');
    Route::get('/{discountCode}', [DiscountCodeController::class, 'show'])->name('show');
    Route::put('/{discountCode}', [DiscountCodeController::class, 'update'])->name('update');
    Route::post('/{discountCode}/deactivate', [DiscountCodeController::class, 'deactivate'])->name('deactivate');
    Route::post('/validate', [DiscountCodeController::class, 'validate'])->name('validate');
});

// Blog Post Management Routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin/blog-posts')->name('admin.blog-posts.')->group(function () {
    Route::get('/', [BlogPostController::class, 'index'])->name('index');
    Route::get('/create', [BlogPostController::class, 'create'])->name('create');
    Route::post('/', [BlogPostController::class, 'store'])->name('store');
    Route::get('/{post}/edit', [BlogPostController::class, 'edit'])->name('edit');
    Route::put('/{post}', [BlogPostController::class, 'update'])->name('update');
    Route::delete('/{post}', [BlogPostController::class, 'destroy'])->name('destroy');
    Route::post('/{post}/duplicate', [BlogPostController::class, 'duplicate'])->name('duplicate');
    Route::post('/upload-image', [BlogPostController::class, 'uploadImage'])->name('upload-image');
    Route::get('/statistics', [BlogPostController::class, 'statistics'])->name('statistics');
});







