<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminToolsController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\BetImportWizardController;
use App\Http\Controllers\Admin\BetManagementController;
use App\Http\Controllers\Admin\BetMassEditController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\CacheController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DiscountCodeController;
use App\Http\Controllers\Admin\LandingPageController;
use App\Http\Controllers\Admin\LeagueController;
use App\Http\Controllers\Admin\Notifications\EmailLogController;
use App\Http\Controllers\Admin\Notifications\EmailTemplateController;
use App\Http\Controllers\Admin\Notifications\PushNotificationController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\SportController;
use App\Http\Controllers\Admin\StripeProductController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UnderConstructionController;
use App\Http\Controllers\Admin\KnowledgebaseController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\BetController;
use App\Http\Controllers\BettingEducationController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageShowController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/pick/{id}', [BetController::class, 'showPick'])->name('pick.show');
Route::get('/odds', [StaticPageController::class, 'odds'])->name('odds');
Route::get('/futures', [StaticPageController::class, 'futures'])->name('futures');
Route::get('/todays-bets', [BetController::class, 'todaysBets'])->name('todays-bets');
Route::get('/buy-our-picks', [StaticPageController::class, 'buyOurPicks'])->name('buy-our-picks');
Route::get('/betting-results', [BetController::class, 'bettingResults'])->name('betting-results');
Route::get('/betting-education', BettingEducationController::class)->name('betting-tips');
Route::get('/partners-offers', [StaticPageController::class, 'partnerOffers'])->name('partner-offers');
Route::get('/careers-jobs', [StaticPageController::class, 'careersJobs'])->name('careers-jobs');
Route::post('/careers/submit-resume', [\App\Http\Controllers\ResumeSubmissionController::class, 'store'])->name('careers.submit-resume');
Route::get('/about-us', [StaticPageController::class, 'aboutUs'])->name('about-us');
Route::get('/faq', [\App\Http\Controllers\FaqController::class, 'index'])->name('faq');

// Customer dashboard route
Route::get('dashboard', [CustomerDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin dashboard uses a different route (admin.dashboard)
Route::get('/bet/{bet}', [BetController::class, 'authenticatedShow'])->middleware(['auth', 'verified'])->name('bet.show');
// Subscription checkout routes
Route::get('/subscription-checkout', [\App\Http\Controllers\SubscriptionController::class, 'checkout'])
    ->name('subscription.checkout')
    ->middleware(['auth', 'verified']);

Route::post('/subscription-process', [\App\Http\Controllers\SubscriptionController::class, 'process'])
        ->name('subscription.process')
        ->middleware(['auth', 'verified']);

Route::post('/validate-coupon', [\App\Http\Controllers\SubscriptionController::class, 'validateCoupon'])
        ->name('subscription.validate-coupon')
        ->middleware(['auth', 'verified']);

// Subscription management routes
Route::middleware(['auth', 'verified'])->prefix('subscription')->name('subscription.')->group(function () {
    Route::post('/switch', [\App\Http\Controllers\SubscriptionController::class, 'switchPlan'])->name('switch');
    Route::post('/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('cancel');
    Route::post('/resume', [\App\Http\Controllers\SubscriptionController::class, 'resume'])->name('resume');
});

// Public Support Route (for both guests and authenticated users)
Route::get('/support', [SupportTicketController::class, 'publicCreate'])->name('support.public');
Route::post('/support', [SupportTicketController::class, 'publicStore'])->name('support.public.store');

// Support Ticket Routes
Route::middleware(['auth', 'verified'])->prefix('support')->name('support.')->group(function () {
    Route::get('/tickets', [SupportTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [SupportTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [SupportTicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/replies', [SupportTicketController::class, 'reply'])->name('tickets.reply');
    Route::put('/tickets/{ticket}/request-close', [SupportTicketController::class, 'requestClose'])->name('tickets.request-close');
    Route::put('/tickets/{ticket}/close', [SupportTicketController::class, 'close'])->name('tickets.close');
    Route::put('/tickets/{ticket}/reopen', [SupportTicketController::class, 'reopen'])->name('tickets.reopen');
});

// Admin Authentication Routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'store'])->middleware('admin.rate_limit:login');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->middleware('auth')->name('admin.logout');
});

// Admin Dashboard
Route::middleware(['auth', AdminMiddleware::class, 'admin.security', 'admin.rate_limit'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Cache management
    Route::post('/cache/clear', [CacheController::class, 'clear'])->name('cache.clear');

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
    Route::resource('bets', BetManagementController::class)->except(['show']);
    Route::post('bets/bulk-update-status', [BetManagementController::class, 'bulkUpdateStatus'])->name('bets.bulk-update-status');
    Route::get('bets/statistics', [BetManagementController::class, 'statistics'])->name('bets.statistics');
    Route::get('bets/export', [BetManagementController::class, 'export'])->name('bets.export')->middleware('admin.rate_limit:export');
    
    // Mass Edit for Golf Each-Way Bets
    Route::prefix('bets/mass-edit')->name('bets.mass-edit.')->group(function () {
        Route::get('/', [BetMassEditController::class, 'index'])->name('index');
        Route::post('/update', [BetMassEditController::class, 'updateBatch'])->name('update');
        Route::post('/import', [BetMassEditController::class, 'importCorrections'])->name('import');
        Route::get('/sample', [BetMassEditController::class, 'downloadSample'])->name('sample');
    });

    // Game Management (TODO)
    // Route::resource('games', Admin\GameManagementController::class);

    // Sport Management
    Route::resource('sports', SportController::class);
    
    // Sport Preferences
    Route::prefix('sport-preferences')->name('sport-preferences.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SportPreferenceController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\SportPreferenceController::class, 'store'])->name('store');
        Route::put('/', [\App\Http\Controllers\Admin\SportPreferenceController::class, 'update'])->name('update');
        Route::delete('/{sportPreference}', [\App\Http\Controllers\Admin\SportPreferenceController::class, 'destroy'])->name('destroy');
    });

    // League Management
    Route::resource('leagues', LeagueController::class);
    Route::get('api/sports/{sport}/leagues', [TeamController::class, 'getLeaguesBySport'])->name('api.sports.leagues');

    // Team Management
    Route::resource('teams', TeamController::class);
    Route::get('api/teams/search', [\App\Http\Controllers\Api\TeamSearchController::class, 'search'])->name('api.teams.search');
    Route::post('api/teams/{team}/update-logo', [\App\Http\Controllers\Api\TeamSearchController::class, 'updateLogo'])->name('api.teams.update-logo');

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

    // Under Construction Settings
    Route::get('under-construction', [UnderConstructionController::class, 'index'])->name('under-construction.index');
    Route::post('under-construction', [UnderConstructionController::class, 'update'])->name('under-construction.update');

    // Testimonial Management
    Route::resource('testimonials', \App\Http\Controllers\Admin\TestimonialController::class);
    Route::post('testimonials/update-order', [\App\Http\Controllers\Admin\TestimonialController::class, 'updateOrder'])->name('testimonials.update-order');

    // FAQ Management
    Route::resource('faqs', \App\Http\Controllers\Admin\FaqController::class);
    Route::post('faqs/{faq}/toggle', [\App\Http\Controllers\Admin\FaqController::class, 'toggle'])->name('faqs.toggle');
    Route::post('faqs/update-order', [\App\Http\Controllers\Admin\FaqController::class, 'updateOrder'])->name('faqs.update-order');

    // Resume Submission Management
    Route::prefix('resume-submissions')->name('resume-submissions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ResumeSubmissionController::class, 'index'])->name('index');
        Route::get('/{resumeSubmission}', [\App\Http\Controllers\Admin\ResumeSubmissionController::class, 'show'])->name('show');
        Route::put('/{resumeSubmission}/update-status', [\App\Http\Controllers\Admin\ResumeSubmissionController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{resumeSubmission}', [\App\Http\Controllers\Admin\ResumeSubmissionController::class, 'destroy'])->name('destroy');

        // Job Position Management
        Route::prefix('positions')->name('positions.')->group(function () {
            Route::post('/', [\App\Http\Controllers\Admin\ResumeSubmissionController::class, 'storePosition'])->name('store');
            Route::put('/{jobPosition}', [\App\Http\Controllers\Admin\ResumeSubmissionController::class, 'updatePosition'])->name('update');
            Route::post('/{jobPosition}/toggle', [\App\Http\Controllers\Admin\ResumeSubmissionController::class, 'togglePosition'])->name('toggle');
            Route::delete('/{jobPosition}', [\App\Http\Controllers\Admin\ResumeSubmissionController::class, 'destroyPosition'])->name('destroy');
        });
    });
});

// New bet import wizard routes
Route::middleware(['auth', AdminMiddleware::class, 'admin.security', 'admin.rate_limit:import'])->prefix('admin/bets/import')->name('admin.bets.import.')->group(function () {
    Route::get('/', [BetImportWizardController::class, 'index'])->name('index');
    Route::post('/upload', [BetImportWizardController::class, 'upload'])->name('upload');
    Route::post('/validate', [BetImportWizardController::class, 'validate'])->name('validate');
    Route::post('/process', [BetImportWizardController::class, 'import'])->name('process');
    Route::get('/progress', [BetImportWizardController::class, 'progress'])->name('progress');
    Route::get('/template', [BetImportWizardController::class, 'downloadTemplate'])->name('template');
    Route::post('/clean', [BetImportWizardController::class, 'cleanCsv'])->name('clean');
    Route::get('/error-report', [BetImportWizardController::class, 'downloadErrorReport'])->name('error-report');
    Route::post('/download-invalid', [BetImportWizardController::class, 'downloadInvalidRows'])->name('download-invalid');
});

// CSRF token refresh route
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf-token');
// Admin tools routes
Route::middleware(['auth', AdminMiddleware::class, 'admin.security', 'admin.rate_limit:export'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/notify-all', [AdminToolsController::class, 'notifyAll'])->name('notify-all');
    Route::get('/bets/export-csv', [AdminToolsController::class, 'exportBets'])->name('bets.export-csv');
});
Route::middleware(['auth', AdminMiddleware::class, 'admin.security', 'admin.rate_limit'])->prefix('admin/pages')->name('admin.pages.')->group(function () {
    Route::get('/', [PageController::class, 'index'])->name('index');
    Route::get('/create', [PageController::class, 'create'])->name('create');
    Route::post('/', [PageController::class, 'store'])->name('store');
    Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
    Route::put('/{page}', [PageController::class, 'update'])->name('update');
    Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
});
Route::get('/pages/{slug}', [PageShowController::class, 'showPage'])->name('pages.show');

// Static page routes that use the CMS
Route::get('/terms', function () {
    $controller = new PageShowController;

    return $controller->showPage('terms-and-conditions');
})->name('terms');

Route::get('/privacy-policy', function () {
    $controller = new PageShowController;

    return $controller->showPage('privacy-policy');
})->name('privacy');

// Redirect /privacy to /privacy-policy
Route::get('/privacy', function () {
    return redirect('/privacy-policy');
});

Route::middleware(['auth', AdminMiddleware::class, 'admin.security', 'admin.rate_limit'])->prefix('admin/customers')->name('admin.customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::get('/create', [CustomerController::class, 'showCreateForm'])->name('create');
    Route::post('/create', [CustomerController::class, 'create'])->name('store');
    Route::get('/{user}', [CustomerController::class, 'show'])->name('show');
    Route::put('/{user}', [CustomerController::class, 'update'])->name('update');
    Route::post('/{user}/impersonate', [\App\Http\Controllers\Admin\ImpersonationController::class, 'start'])->name('impersonate');
    Route::post('/{user}/password-reset', [CustomerController::class, 'sendPasswordReset'])->name('password-reset');
    Route::post('/{user}/cancel-subscription', [CustomerController::class, 'cancelSubscription'])->name('cancel-subscription');
    Route::get('/export', [CustomerController::class, 'export'])->name('export')->middleware('admin.rate_limit:export');
});

// Admin API routes
Route::middleware(['auth', AdminMiddleware::class, 'admin.security', 'admin.rate_limit'])->prefix('admin/api')->name('admin.api.')->group(function () {
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
});

// Impersonation stop route (accessible when impersonating)
Route::get('/admin/impersonate/stop', [\App\Http\Controllers\Admin\ImpersonationController::class, 'stop'])
    ->name('admin.impersonate.stop')
    ->middleware('auth');

Route::middleware(['auth', AdminMiddleware::class, 'admin.security', 'admin.rate_limit'])->prefix('admin/admins')->name('admin.admins.')->group(function () {
    Route::get('/', [AdminUserController::class, 'index'])->name('index');
    Route::post('/add', [AdminUserController::class, 'add'])->name('add');
    Route::post('/remove', [AdminUserController::class, 'remove'])->name('remove');
});

Route::middleware(['auth', AdminMiddleware::class, 'admin.security', 'admin.rate_limit'])->prefix('admin/landing-pages')->name('admin.landing-pages.')->group(function () {
    Route::get('/', [LandingPageController::class, 'index'])->name('index');
    Route::get('/create', [LandingPageController::class, 'create'])->name('create');
    Route::post('/', [LandingPageController::class, 'store'])->name('store');
    Route::get('/{page}/edit', [LandingPageController::class, 'edit'])->name('edit');
    Route::put('/{page}', [LandingPageController::class, 'update'])->name('update');
    Route::delete('/{page}', [LandingPageController::class, 'destroy'])->name('destroy');
});
Route::get('/landing/{slug}', [PageShowController::class, 'showLandingPage'])->name('landing.show');

// Blog routes are defined in routes/blog.php and loaded via bootstrap/app.php

// Stripe Product Management Routes
Route::middleware(['auth', AdminMiddleware::class, 'admin.security', 'admin.rate_limit'])->prefix('admin/stripe-products')->name('admin.stripe-products.')->group(function () {
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
    Route::post('/{stripeProduct}/create-price-version', [StripeProductController::class, 'createPriceVersion'])->name('create-price-version');
    Route::get('/price-history', [StripeProductController::class, 'priceHistory'])->name('price-history');
});

// Subscription Dashboard Routes - Merged into Customers

// Discount Code Management Routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin/discounts')->name('admin.discounts.')->group(function () {
    Route::get('/', [DiscountCodeController::class, 'index'])->name('index');
    Route::post('/', [DiscountCodeController::class, 'store'])->name('store');
    Route::get('/{discountCode}', [DiscountCodeController::class, 'show'])->name('show');
    Route::put('/{discountCode}', [DiscountCodeController::class, 'update'])->name('update');
    Route::post('/{discountCode}/deactivate', [DiscountCodeController::class, 'deactivate'])->name('deactivate');
    Route::post('/validate', [DiscountCodeController::class, 'validate'])->name('validate');
});

// Knowledgebase Routes
Route::middleware(['auth', AdminMiddleware::class, 'admin.security'])->prefix('admin/knowledgebase')->name('admin.knowledgebase.')->group(function () {
    Route::get('/', [KnowledgebaseController::class, 'index'])->name('index');
    Route::get('/api/page', [KnowledgebaseController::class, 'getForPage'])->name('api.page');
});

// Knowledgebase API route accessible by admins from anywhere (including frontend)
Route::middleware(['auth'])->get('/api/knowledgebase/page', [KnowledgebaseController::class, 'getForPage'])->name('knowledgebase.api.page');

// Notifications Management Routes
Route::middleware(['auth', AdminMiddleware::class, 'admin.security', 'admin.rate_limit'])->prefix('admin/notifications')->name('admin.notifications.')->group(function () {
    // Email Templates
    Route::prefix('email-templates')->name('email-templates.')->group(function () {
        Route::get('/', [EmailTemplateController::class, 'index'])->name('index');
        Route::get('/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('edit');
        Route::put('/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('update');
        Route::get('/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->name('preview');
        Route::post('/{emailTemplate}/reset', [EmailTemplateController::class, 'reset'])->name('reset');
        Route::post('/{emailTemplate}/send-test', [EmailTemplateController::class, 'sendTest'])->name('send-test');
    });

    // Email Logs
    Route::prefix('email-logs')->name('email-logs.')->group(function () {
        Route::get('/', [EmailLogController::class, 'index'])->name('index');
        Route::get('/{emailLog}', [EmailLogController::class, 'show'])->name('show');
        Route::post('/{emailLog}/resend', [EmailLogController::class, 'resend'])->name('resend');
    });
    // Push Notifications
    Route::prefix('push')->name('push.')->group(function () {
        Route::get('/', [PushNotificationController::class, 'index'])->name('index');
        Route::get('/create', [PushNotificationController::class, 'create'])->name('create');
        Route::post('/send', [PushNotificationController::class, 'send'])->name('send');
        Route::post('/test', [PushNotificationController::class, 'test'])->name('test');
        Route::get('/debug', function () {
            return Inertia::render('admin/Notifications/PushNotifications/Debug');
        })->name('debug');
    });
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

// Blog Category Management Routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin/blog-categories')->name('admin.blog-categories.')->group(function () {
    Route::get('/', [BlogCategoryController::class, 'index'])->name('index');
    Route::post('/', [BlogCategoryController::class, 'store'])->name('store');
    Route::put('/{blogCategory}', [BlogCategoryController::class, 'update'])->name('update');
    Route::delete('/{blogCategory}', [BlogCategoryController::class, 'destroy'])->name('destroy');
    Route::post('/update-order', [BlogCategoryController::class, 'updateOrder'])->name('update-order');
});

// Media Library Routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin/media-library')->name('admin.media-library.')->group(function () {
    Route::get('/', [MediaLibraryController::class, 'index'])->name('index');
    Route::post('/upload', [MediaLibraryController::class, 'store'])->name('store');
    Route::delete('/{media}', [MediaLibraryController::class, 'destroy'])->name('destroy');
    Route::get('/picker', [MediaLibraryController::class, 'picker'])->name('picker');
});

// Affiliate Management Routes
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin/affiliates')->name('admin.affiliates.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AffiliateController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\AffiliateController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\AffiliateController::class, 'store'])->name('store');
    Route::get('/{affiliate}', [\App\Http\Controllers\Admin\AffiliateController::class, 'show'])->name('show');
    Route::get('/{affiliate}/edit', [\App\Http\Controllers\Admin\AffiliateController::class, 'edit'])->name('edit');
    Route::put('/{affiliate}', [\App\Http\Controllers\Admin\AffiliateController::class, 'update'])->name('update');
    Route::delete('/{affiliate}', [\App\Http\Controllers\Admin\AffiliateController::class, 'destroy'])->name('destroy');
    Route::get('/{affiliate}/customers', [\App\Http\Controllers\Admin\AffiliateController::class, 'customers'])->name('customers');
});

// Stripe Webhook - Laravel Cashier
Route::post(
    'stripe/webhook',
    '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook'
)->name('cashier.webhook');
