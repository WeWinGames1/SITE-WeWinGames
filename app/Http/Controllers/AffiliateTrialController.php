<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuickCheckoutRequest;
use App\Models\DiscountCode;
use App\Models\StripeProduct;
use App\Services\QuickCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AffiliateTrialController extends Controller
{
    private const TRIAL_DAYS = 7;

    private const BILLING_PERIOD = 'monthly';

    public function __construct(
        protected QuickCheckoutService $checkoutService
    ) {}

    /**
     * Show the affiliate trial checkout page
     */
    public function show(Request $request): Response|RedirectResponse
    {
        // Feature flag check
        if (! config('features.affiliate_trial_enabled')) {
            return redirect()->route('login')
                ->with('error', 'This checkout is currently unavailable.');
        }

        $request->validate([
            'plan' => 'nullable|string|in:silver,gold,platinum,Silver,Gold,Platinum',
        ]);

        // Convert plan to proper case (default to Gold)
        $plan = ucfirst(strtolower($request->query('plan', 'gold')));

        // Get the product for the selected plan (monthly only)
        $product = StripeProduct::where('tier', $plan)
            ->where('billing_period', self::BILLING_PERIOD)
            ->where('is_active', true)
            ->whereNotNull('stripe_price_id')
            ->first();

        if (! $product) {
            // Fallback to Gold monthly
            $product = StripeProduct::where('tier', 'Gold')
                ->where('billing_period', self::BILLING_PERIOD)
                ->where('is_active', true)
                ->first();
        }

        if (! $product) {
            return redirect()->route('login')
                ->with('error', 'No subscription plans are currently available. Please contact support.');
        }

        // Get all monthly products for plan selection
        $monthlyProducts = StripeProduct::active()
            ->where('billing_period', self::BILLING_PERIOD)
            ->whereNotNull('stripe_price_id')
            ->get()
            ->keyBy(function ($product) {
                return strtolower($product->tier);
            })
            ->map(function ($product) {
                return [
                    'price_id' => $product->stripe_price_id,
                    'product_id' => $product->stripe_product_id,
                    'amount' => $product->price,
                    'tier' => $product->tier,
                    'billing_period' => $product->billing_period,
                    'features' => $product->features,
                ];
            });

        return Inertia::render('AffiliateTrial', [
            'selectedPlan' => [
                'tier' => $product->tier,
                'name' => ucfirst($product->tier),
                'price' => '$'.number_format($product->price, 0),
                'priceAmount' => $product->price,
                'period' => $product->billing_period,
                'priceId' => $product->stripe_price_id,
                'productId' => $product->stripe_product_id,
                'features' => $product->features ?? [],
            ],
            'monthlyProducts' => $monthlyProducts,
            'trialDays' => self::TRIAL_DAYS,
            'stripeKey' => config('cashier.key'),
            'discountCode' => $request->query('discountCode'),
        ]);
    }

    /**
     * Process the affiliate trial checkout
     */
    public function process(QuickCheckoutRequest $request): RedirectResponse
    {
        // Feature flag check
        if (! config('features.affiliate_trial_enabled')) {
            return redirect()->route('login')
                ->with('error', 'This checkout is currently unavailable.');
        }

        // Process with 7-day trial and affiliate_trial registration type
        $result = $this->checkoutService->processCheckout(
            $request,
            $request->input('price_id'),
            $request->input('payment_method'),
            self::TRIAL_DAYS,
            'affiliate_trial'
        );

        if (! $result['success']) {
            if ($result['error'] === 'email_exists') {
                return back()->withErrors([
                    'email' => $result['message'].' <a href="'.route('login').'" class="text-decoration-underline">Log in here</a>',
                ])->withInput();
            }

            return back()->withErrors([
                'payment' => $result['message'],
            ])->withInput();
        }

        // Get purchase data for tracking
        $stripeProduct = StripeProduct::where('stripe_price_id', $request->input('price_id'))->first();
        $purchaseValue = $stripeProduct ? $stripeProduct->price : 0;

        // Apply discount to purchase value for tracking
        if ($request->filled('coupon')) {
            $discountCode = DiscountCode::where('code', $request->coupon)->active()->first();
            if ($discountCode) {
                if ($discountCode->discount_type === 'percentage') {
                    $purchaseValue = $purchaseValue * (1 - $discountCode->discount_amount / 100);
                } else {
                    $purchaseValue = max(0, $purchaseValue - $discountCode->discount_amount);
                }
            }
        }

        $purchaseData = [
            'plan_name' => $stripeProduct ? ucfirst($stripeProduct->tier).' Plan' : 'Subscription',
            'plan_price' => $purchaseValue,
            'billing_period' => self::BILLING_PERIOD,
            'trial_days' => self::TRIAL_DAYS,
        ];

        // Redirect to completion page
        return redirect()->route('complete-registration', [
            'token' => $result['user']->completion_token,
        ])->with([
            'success' => 'Payment method saved! Your 7-day free trial has started. Complete your account setup below.',
            'purchase_data' => $purchaseData,
        ]);
    }
}
