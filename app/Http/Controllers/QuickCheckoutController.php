<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteRegistrationRequest;
use App\Http\Requests\QuickCheckoutRequest;
use App\Models\DiscountCode;
use App\Models\StripeProduct;
use App\Models\User;
use App\Services\QuickCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class QuickCheckoutController extends Controller
{
    public function __construct(
        protected QuickCheckoutService $checkoutService
    ) {}

    /**
     * Show the quick checkout page
     */
    public function show(Request $request): Response|RedirectResponse
    {
        // Feature flag check - redirect to login (not register, since register redirects here)
        if (! config('features.quick_checkout_enabled')) {
            return redirect()->route('login')
                ->with('error', 'Quick checkout is temporarily unavailable. Please log in or contact support.');
        }

        $request->validate([
            'plan' => 'nullable|string|in:silver,gold,platinum,Silver,Gold,Platinum',
            'period' => 'nullable|string|in:daily,weekly,monthly',
        ]);

        // Convert plan to proper case (database stores as Bronze, Silver, Gold, Platinum)
        $plan = ucfirst(strtolower($request->query('plan', 'gold')));
        $period = $request->query('period', 'monthly');

        // Get the product for the selected plan and period
        $product = StripeProduct::where('tier', $plan)
            ->where('billing_period', $period)
            ->where('is_active', true)
            ->whereNotNull('stripe_price_id')
            ->first();

        if (! $product) {
            // Fallback to Gold monthly
            $product = StripeProduct::where('tier', 'Gold')
                ->where('billing_period', 'monthly')
                ->where('is_active', true)
                ->first();
        }

        // If still no product found, redirect to login with error
        if (! $product) {
            return redirect()->route('login')
                ->with('error', 'No subscription plans are currently available. Please contact support.');
        }

        // Get all active products for plan selection
        $allProducts = StripeProduct::active()
            ->whereNotNull('stripe_price_id')
            ->get()
            ->groupBy('billing_period')
            ->map(function ($products) {
                return $products->keyBy(function ($product) {
                    return strtolower($product->tier);
                })->map(function ($product) {
                    return [
                        'price_id' => $product->stripe_price_id,
                        'product_id' => $product->stripe_product_id,
                        'amount' => $product->price,
                        'tier' => $product->tier,
                        'billing_period' => $product->billing_period,
                        'features' => $product->features,
                    ];
                });
            });

        return Inertia::render('QuickCheckout', [
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
            'allProducts' => $allProducts,
            'stripeKey' => config('cashier.key'),
            'discountCode' => $request->query('discountCode'),
        ]);
    }

    /**
     * Process the quick checkout
     */
    public function process(QuickCheckoutRequest $request): RedirectResponse
    {
        Log::info('QuickCheckout: Starting checkout process', [
            'email' => $request->input('email'),
            'price_id' => $request->input('price_id'),
        ]);

        // Feature flag check - redirect to login (not register, since register redirects here)
        if (! config('features.quick_checkout_enabled')) {
            return redirect()->route('login')
                ->with('error', 'Quick checkout is temporarily unavailable.');
        }

        try {
            $result = $this->checkoutService->processCheckout(
                $request,
                $request->input('price_id'),
                $request->input('payment_method')
            );
        } catch (\Throwable $e) {
            Log::error('QuickCheckout: Exception during checkout', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'payment' => 'An error occurred: '.$e->getMessage(),
            ])->withInput();
        }

        if (! $result['success']) {
            Log::warning('QuickCheckout: Checkout failed', [
                'email' => $request->input('email'),
                'error' => $result['error'] ?? 'unknown',
                'message' => $result['message'] ?? 'No message',
            ]);

            if ($result['error'] === 'email_exists') {
                return back()->withErrors([
                    'email' => $result['message'].' <a href="'.route('login').'" class="text-decoration-underline">Log in here</a>',
                ])->withInput();
            }

            return back()->withErrors([
                'payment' => $result['message'],
            ])->withInput();
        }

        // Stripe still needs the customer: a Cash App Pay hand-off or a 3DS
        // challenge. Send the client secret back to the same page so Stripe.js can
        // run it with a return_url — something a server-side confirm cannot do.
        if ($result['requires_action'] ?? false) {
            Log::info('QuickCheckout: Payment requires customer action', [
                'email' => $request->input('email'),
                'user_id' => $result['user']->id ?? null,
            ]);

            return back()->with([
                'requires_action' => true,
                'payment_intent_client_secret' => $result['client_secret'],
                'payment_return_url' => route('quick-checkout.return', [
                    'token' => $result['completion_token'],
                ]),
            ]);
        }

        Log::info('QuickCheckout: Checkout successful', [
            'email' => $request->input('email'),
            'user_id' => $result['user']->id ?? null,
        ]);

        return redirect()->route('complete-registration', [
            'token' => $result['user']->completion_token,
        ])->with([
            'success' => 'Payment successful! Complete your account setup below.',
            'purchase_data' => $this->purchaseData(
                $request->input('price_id'),
                $request->input('coupon'),
                $result['amount_paid'] ?? null,
                $result['payment_intent_id'] ?? null,
            ),
        ]);
    }

    /**
     * Landing point after the customer confirms the payment in the browser —
     * both the real Stripe redirect back from Cash App and the in-page
     * navigation Stripe.js makes when it settles without leaving the site.
     */
    public function paymentReturn(Request $request): RedirectResponse
    {
        $token = $request->query('token');

        $user = is_string($token)
            ? $this->checkoutService->findUserByToken($token)
            : null;

        if (! $user) {
            return redirect()->route('password.request')
                ->with('info', 'This completion link has expired. Please use password reset to access your account.');
        }

        // Read the plan before finishing: a failed payment deletes the account
        // along with the subscription that names it, and the customer should land
        // back on the plan they were actually buying.
        $priceId = $user->subscriptions()->latest('id')->value('stripe_price');

        $result = $this->checkoutService->completePendingPayment($user);

        if (! $result['success']) {
            Log::warning('QuickCheckout: Payment return did not complete', [
                'user_id' => $user->id,
                'error' => $result['error'] ?? 'unknown',
            ]);

            return redirect()->route('quick-checkout', $this->planQuery($priceId))
                ->with('error', $result['message'] ?? 'We could not complete your payment. Please try again.');
        }

        return redirect()->route('complete-registration', [
            'token' => $user->completion_token,
        ])->with([
            'success' => ($result['processing'] ?? false)
                ? 'Payment received and still settling with Cash App. Complete your account setup below.'
                : 'Payment successful! Complete your account setup below.',
            'purchase_data' => $this->purchaseData(
                $priceId,
                $result['coupon'] ?? null,
                $result['amount_paid'] ?? null,
                $result['payment_intent_id'] ?? null,
            ),
        ]);
    }

    /**
     * Query parameters that put the checkout page back on a given price's plan.
     * Only values show() accepts are returned, so a retired or Bronze-tier price
     * sends the customer to the default plan instead of tripping validation.
     *
     * @return array{plan?: string, period?: string}
     */
    protected function planQuery(?string $priceId): array
    {
        $product = $priceId
            ? StripeProduct::where('stripe_price_id', $priceId)->first()
            : null;

        if (! $product) {
            return [];
        }

        $plan = strtolower((string) $product->tier);
        $period = (string) $product->billing_period;

        return array_filter([
            'plan' => in_array($plan, ['silver', 'gold', 'platinum'], true) ? $plan : null,
            'period' => in_array($period, ['daily', 'weekly', 'monthly'], true) ? $period : null,
        ]);
    }

    /**
     * Build the analytics payload flashed to the completion page.
     *
     * @return array{plan_name: string, plan_price: float|int, billing_period: string, conversion_id: ?string}
     */
    protected function purchaseData(?string $priceId, ?string $coupon, ?float $amountPaid, ?string $paymentIntentId): array
    {
        $stripeProduct = $priceId
            ? StripeProduct::where('stripe_price_id', $priceId)->first()
            : null;

        $purchaseValue = $stripeProduct ? $stripeProduct->price : 0;

        // Apply discount to purchase value for tracking
        if ($coupon) {
            $discountCode = DiscountCode::where('code', $coupon)->active()->first();
            if ($discountCode) {
                if ($discountCode->discount_type === 'percentage') {
                    $purchaseValue = $purchaseValue * (1 - $discountCode->discount_amount / 100);
                } else {
                    $purchaseValue = max(0, $purchaseValue - $discountCode->discount_amount);
                }
            }
        }

        return [
            'plan_name' => $stripeProduct ? ucfirst($stripeProduct->tier).' Plan' : 'Subscription',
            // Prefer what Stripe actually charged over the locally computed
            // list-price-minus-coupon, so the browser pixel and the server
            // Conversion API report the same value for the same conversion_id.
            'plan_price' => $amountPaid ?? $purchaseValue,
            'billing_period' => $stripeProduct?->billing_period ?? 'monthly',
            'conversion_id' => $paymentIntentId,
        ];
    }

    /**
     * Show the complete registration page
     */
    public function showComplete(Request $request): Response|RedirectResponse
    {
        $token = $request->query('token');

        if (! $token) {
            return redirect()->route('login')
                ->with('error', 'Invalid completion link. Please use the link from your email or reset your password.');
        }

        $user = $this->checkoutService->findUserByToken($token);

        if (! $user) {
            return redirect()->route('password.request')
                ->with('info', 'This completion link has expired. Please use password reset to access your account.');
        }

        return Inertia::render('CompleteRegistration', [
            'token' => $token,
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    /**
     * Complete the registration (set password)
     */
    public function complete(CompleteRegistrationRequest $request): RedirectResponse
    {
        $user = $this->checkoutService->findUserByToken($request->input('token'));

        if (! $user) {
            return redirect()->route('password.request')
                ->with('error', 'This completion link has expired. Please use password reset to access your account.');
        }

        $result = $this->checkoutService->completeRegistration(
            $user,
            $request->input('password'),
            $request->input('discord_username')
        );

        if (! $result['success']) {
            return back()->withErrors([
                'password' => $result['message'],
            ]);
        }

        // Log the user in
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to WeWinGames! Your account is now fully set up.');
    }

    /**
     * Validate a discount code via AJAX
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'product_id' => 'nullable|string',
        ]);

        $discountCode = DiscountCode::where('code', $request->code)
            ->active()
            ->first();

        if (! $discountCode) {
            return response()->json(['valid' => false]);
        }

        if (! $discountCode->isValid()) {
            return response()->json(['valid' => false, 'message' => 'This discount code is no longer valid']);
        }

        // Check product restrictions if product ID is provided
        if ($request->filled('product_id') && ! $discountCode->appliesToProduct($request->product_id)) {
            return response()->json(['valid' => false, 'message' => 'This discount code does not apply to the selected product']);
        }

        return response()->json([
            'valid' => true,
            'discount' => [
                'percent_off' => $discountCode->percent_off,
                'amount_off' => $discountCode->amount_off,
            ],
        ]);
    }

    /**
     * Resend completion email
     */
    public function resendCompletion(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', strtolower($request->email))
            ->where('status', 'pending_setup')
            ->where('registration_type', 'quick_checkout')
            ->first();

        if (! $user) {
            // Don't reveal whether the email exists
            return back()->with('info', 'If an account with this email exists and needs completion, you will receive an email shortly.');
        }

        $this->checkoutService->resendCompletionEmail($user);

        return back()->with('success', 'Completion email has been resent. Please check your inbox.');
    }
}
