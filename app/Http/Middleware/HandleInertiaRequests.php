<?php

namespace App\Http\Middleware;

use App\Http\Resources\UserResource;
use App\Models\Faq;
use App\Services\BetService;
use App\Services\SimpleCacheService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $sharedData = [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? new UserResource($request->user()) : null,
                'isSubscribed' => $request->user() ? $request->user()->hasActiveSubscription() : false,
                'isAdmin' => $request->user() ? $request->user()->hasRole('admin') : false,
                'isAmbassador' => $request->user() ? $request->user()->is_ambassador : false,
                'isGifted' => $request->user() ? $request->user()->is_gifted : false,
                'currentTier' => $request->user() ? $request->user()->getCurrentTier() : null,
            ],
            'impersonation' => [
                'isImpersonating' => session()->has('impersonator_id'),
                'impersonatorId' => session()->get('impersonator_id'),
            ],
            'stripeKey' => config('cashier.key'),
        ];

        // Include admin pages only if the user is an admin
        if ($request->user() && $request->user()->hasRole('admin')) {
            // Only load admin pages for navigation, not all bets
            $sharedData['adminPages'] = \App\Models\Page::orderBy('title')->get(['id', 'title', 'slug']);
            
            // Note: Bets should be loaded on specific pages that need them, not globally
            // This prevents memory exhaustion from loading thousands of records
        }

        // Get active Stripe products
        $stripeProducts = \App\Models\StripeProduct::active()
            ->whereNotNull('stripe_price_id')
            ->get()
            ->groupBy('billing_period')
            ->map(function ($products) {
                return $products->keyBy(function ($product) {
                    return strtolower($product->tier);
                })->map(function ($product) {
                    return [
                        'price_id' => $product->stripe_price_id,
                        'amount' => $product->price,
                        'features' => $product->features,
                    ];
                });
            });

        // Get footer FAQs (limit to 4 for footer display)
        $footerFaqs = SimpleCacheService::rememberQuery(
            'footer_faqs',
            SimpleCacheService::TTL_LONG,
            fn () => Faq::active()
                ->ordered()
                ->limit(4)
                ->get(['id', 'question', 'answer'])
        );

        return array_merge($sharedData, [
            'stripeProducts' => $stripeProducts,
            'stripePrices' => [
                // Legacy support - to be removed
                'gold_monthly' => $stripeProducts['monthly']['gold']['price_id'] ?? env('GOLD_MONTHLY'),
                'silver_monthly' => $stripeProducts['monthly']['silver']['price_id'] ?? env('SILVER_MONTHLY'),
                'platinum_monthly' => $stripeProducts['monthly']['platinum']['price_id'] ?? env('PLATINUM_MONTHLY'),
                'gold_weekly' => $stripeProducts['weekly']['gold']['price_id'] ?? env('GOLD_WEEKLY'),
                'silver_weekly' => $stripeProducts['weekly']['silver']['price_id'] ?? env('SILVER_WEEKLY'),
                'platinum_weekly' => $stripeProducts['weekly']['platinum']['price_id'] ?? env('PLATINUM_WEEKLY'),
                'gold_daily' => $stripeProducts['daily']['gold']['price_id'] ?? env('GOLD_DAILY'),
                'silver_daily' => $stripeProducts['daily']['silver']['price_id'] ?? env('SILVER_DAILY'),
                'platinum_daily' => $stripeProducts['daily']['platinum']['price_id'] ?? env('PLATINUM_DAILY'),
            ],
            'env' => [
                'APP_ENV' => config('app.env'),
                'APP_DEBUG' => config('app.debug'),
                'GOOGLE_ANALYTICS_TAG_ID' => config('google.analytics.tag_id'),
                'GOOGLE_TAG_MANAGER_ID' => config('google.tag_manager.container_id'),
                'TURNSTILE_ENABLED' => config('services.turnstile.enabled'),
                'TURNSTILE_SITE_KEY' => config('services.turnstile.site_key'),
            ],
            'social' => [
                'links' => config('social.links'),
                'icons' => config('social.icons'),
            ],
            'footerFaqs' => $footerFaqs,
        ]);
    }
}
