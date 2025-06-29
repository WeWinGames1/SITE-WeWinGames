<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = User::with('subscriptions')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        return Inertia::render('admin/CustomersIndex', [
            'customers' => $customers,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'subscription_price' => 'nullable|string',
            'subscription_status' => 'required|string',
            'trial_days' => 'nullable|integer|min:0',
        ]);

        // Grant trial if requested (do this first)
        if (!empty($data['trial_days'])) {
            $trialUntil = now()->addDays($data['trial_days']);
            if ($user->hasPaymentMethod()) {
                // If user has a payment method and an active subscription, set trial on subscription
                $subscription = $user->subscriptions()->active()->first();
                if ($subscription) {
                    $subscription->trialUntil($trialUntil);
                }
            } else {
                // Otherwise, update the user's trial_ends_at property
                $user->trial_ends_at = $trialUntil;
                $user->save();
            }
        }

        // Assign a new subscription if a price is provided
        if (!empty($data['subscription_price']) && empty($data['trial_days'])) {
            // Cancel current subscription if exists
            $current = $user->subscriptions()->active()->first();
            if ($current) {
                $current->cancelNow();
            }
            // Create new subscription
            $user->newSubscription('default', $data['subscription_price'])->create();
        }

        // Update status if needed
        $subscription = $user->subscriptions()->latest()->first();
        if ($subscription) {
            $subscription->stripe_status = $data['subscription_status'];
            $subscription->save();
        }

        return back()->with('success', 'Subscription updated!');
    }
}