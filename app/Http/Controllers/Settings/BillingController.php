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
        
        return Inertia::render('settings/Billing',[
            'subscriptions' => $request->user()->subscriptions
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
