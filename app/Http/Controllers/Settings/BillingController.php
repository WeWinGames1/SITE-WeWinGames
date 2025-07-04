<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\StripeProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\StripePriceVersioningService;

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
        
        if ($activeSubscription && $activeSubscription->active()) {
            $priceId = $activeSubscription->stripe_price;
            
            // First try to get from StripeProduct table
            $stripeProduct = StripeProduct::where('stripe_price_id', $priceId)
                ->where('is_active', true)
                ->first();
                
            if ($stripeProduct) {
                $currentPlan = [
                    'tier' => $stripeProduct->tier,
                    'period' => $stripeProduct->billing_period,
                    'price_id' => $priceId,
                    'status' => $activeSubscription->stripe_status,
                    'ends_at' => $activeSubscription->ends_at,
                    'trial_ends_at' => $activeSubscription->trial_ends_at,
                    'current_period_end' => $activeSubscription->current_period_end ?? $activeSubscription->created_at->addMonth(),
                ];
            } else {
                // Fallback to config
                $stripePrices = config('stripe.price_to_tier', []);
                if (isset($stripePrices[$priceId])) {
                    $currentPlan = [
                        'tier' => $stripePrices[$priceId]['tier'],
                        'period' => $stripePrices[$priceId]['period'],
                        'price_id' => $priceId,
                        'status' => $activeSubscription->stripe_status,
                        'ends_at' => $activeSubscription->ends_at,
                        'trial_ends_at' => $activeSubscription->trial_ends_at,
                        'current_period_end' => $activeSubscription->current_period_end ?? $activeSubscription->created_at->addMonth(),
                    ];
                }
            }
        }
        
        // Get payment methods
        $paymentMethods = [];
        if ($user->hasStripeId()) {
            try {
                // Fetch ALL payment methods from Stripe (including all types)
                $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                $stripeMethods = $stripe->paymentMethods->all([
                    'customer' => $user->stripe_id,
                    'limit' => 100
                ]);
                
                foreach ($stripeMethods->data as $pm) {
                    if ($pm->type === 'card') {
                        $paymentMethods[] = [
                            'id' => $pm->id,
                            'brand' => ucfirst($pm->card->brand),
                            'last4' => $pm->card->last4,
                            'exp_month' => $pm->card->exp_month,
                            'exp_year' => $pm->card->exp_year,
                            'is_default' => $pm->id === optional($user->defaultPaymentMethod())->id,
                        ];
                    } elseif ($pm->type === 'link') {
                        // Link payment methods store card details differently
                        // Try to extract meaningful display information
                        $displayInfo = 'Link';
                        
                        // Link payment methods might have email associated
                        if (isset($pm->link->email)) {
                            // Use masked email for display
                            $email = $pm->link->email;
                            $parts = explode('@', $email);
                            if (count($parts) == 2) {
                                $username = $parts[0];
                                $domain = $parts[1];
                                // Mask the username part
                                $maskedUsername = substr($username, 0, 2) . '***';
                                $displayInfo = $maskedUsername . '@' . $domain;
                            }
                        }
                        
                        $paymentMethods[] = [
                            'id' => $pm->id,
                            'brand' => 'Stripe Link',
                            'last4' => $displayInfo,
                            'exp_month' => null,
                            'exp_year' => null,
                            'is_default' => $pm->id === optional($user->defaultPaymentMethod())->id,
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to fetch payment methods: ' . $e->getMessage());
            }
        }
        
        // Get recent invoices
        $invoices = [];
        if ($user->hasStripeId()) {
            try {
                $invoices = $user->invoices()->take(10)->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'number' => $invoice->number ?? 'N/A',
                        'date' => $invoice->date()->toFormattedDateString(),
                        'total' => $invoice->total(),
                        'status' => $invoice->status ?? 'paid',
                        'pdf' => $invoice->invoice_pdf ?? '#',
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
            
            return $user->redirectToBillingPortal(route('billing.edit') . '?from=stripe');
        } catch (\Exception $e) {
            return redirect()->route('billing.edit')
                ->with('error', 'Unable to access billing portal. Please contact support.');
        }
    }
    /**
     * Set a payment method as default
     */
    public function setDefaultPaymentMethod(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_method_id' => 'required|string',
        ]);
        
        try {
            $user = $request->user();
            
            if (!$user->hasStripeId()) {
                return back()->with('error', 'No Stripe customer found.');
            }
            
            // Update the default payment method
            $user->updateDefaultPaymentMethod($request->payment_method_id);
            
            return back()->with('success', 'Default payment method updated successfully.');
            
        } catch (\Exception $e) {
            \Log::error('Failed to set default payment method: ' . $e->getMessage());
            return back()->with('error', 'Failed to update default payment method. Please try again.');
        }
    }
}
