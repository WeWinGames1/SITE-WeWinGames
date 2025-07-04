<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    /**
     * Show the user's billing settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        
        // Get active subscription with more details
        $activeSubscription = $user->subscription('default');
        $currentPlan = null;
        $stripePrices = config('stripe.price_to_tier', []);
        
        if ($activeSubscription && $activeSubscription->active()) {
            $priceId = $activeSubscription->stripe_price;
            if (isset($stripePrices[$priceId])) {
                $currentPlan = [
                    'tier' => $stripePrices[$priceId]['tier'],
                    'period' => $stripePrices[$priceId]['period'],
                    'price_id' => $priceId,
                    'status' => $activeSubscription->stripe_status,
                    'ends_at' => $activeSubscription->ends_at,
                    'trial_ends_at' => $activeSubscription->trial_ends_at,
                    'current_period_end' => $activeSubscription->currentPeriodEnd(),
                ];
            }
        }
        
        // Get payment methods
        $paymentMethods = [];
        if ($user->hasStripeId()) {
            try {
                $paymentMethods = $user->paymentMethods()->map(function ($pm) {
                    return [
                        'id' => $pm->id,
                        'brand' => $pm->card->brand,
                        'last4' => $pm->card->last4,
                        'exp_month' => $pm->card->exp_month,
                        'exp_year' => $pm->card->exp_year,
                        'is_default' => $pm->id === optional($user->defaultPaymentMethod())->id,
                    ];
                });
            } catch (\Exception $e) {
                // Handle error silently
            }
        }
        
        // Get recent invoices
        $invoices = [];
        if ($user->hasStripeId()) {
            try {
                $invoices = $user->invoices()->take(10)->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'number' => $invoice->number,
                        'date' => $invoice->date()->toFormattedDateString(),
                        'total' => $invoice->total(),
                        'status' => $invoice->status,
                        'pdf' => $invoice->invoicePdf(),
                    ];
                });
            } catch (\Exception $e) {
                // Handle error silently
            }
        }
        
        return Inertia::render('settings/Billing', [
            'subscriptions' => $user->subscriptions,
            'currentPlan' => $currentPlan,
            'paymentMethods' => $paymentMethods,
            'invoices' => $invoices,
            'hasStripeId' => $user->hasStripeId(),
        ]);
    }

    public function billing_portal(Request $request): RedirectResponse {
        $user = $request->user();
        
        // Check if Stripe is configured
        if (empty(config('cashier.secret'))) {
            return redirect()->route('billing.edit')
                ->with('error', 'Stripe is not configured. Please contact support.');
        }
        
        try {
            // Create as Stripe customer if not already
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer();
            }
            
            return $user->redirectToBillingPortal(route('billing.edit'));
        } catch (\Exception $e) {
            return redirect()->route('billing.edit')
                ->with('error', 'Unable to access billing portal. Please contact support.');
        }
    }
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        // $validated = $request->validate([
        //     'current_password' => ['required', 'current_password'],
        //     'password' => ['required', Password::defaults(), 'confirmed'],
        // ]);

        // $request->user()->update([
        //     'password' => Hash::make($validated['password']),
        // ]);

        return back();
    }
}
