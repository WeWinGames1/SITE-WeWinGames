<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SimpleCacheService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['subscriptions' => function ($q) {
            $q->latest();
        }])
            ->withCount('subscriptions');

        // Search functionality
        if ($search = $request->get('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('id', 'like', '%'.$search.'%');
            });
        }

        // User Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Subscription Status filter
        if ($subscriptionStatus = $request->get('subscription_status')) {
            if ($subscriptionStatus === 'no_subscription') {
                $query->doesntHave('subscriptions');
            } else {
                $query->whereHas('subscriptions', function (Builder $q) use ($subscriptionStatus) {
                    $q->where('stripe_status', $subscriptionStatus);
                });
            }
        }

        // Tier filter (Free, Silver, Gold, Platinum)
        if ($tier = $request->get('tier')) {
            if ($tier === 'free') {
                $query->doesntHave('subscriptions');
            } else {
                $query->whereHas('subscriptions', function (Builder $q) use ($tier) {
                    $q->where('stripe_price', 'like', '%'.strtolower($tier).'%');
                });
            }
        }

        // Date range filter
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        // Handle special sort cases
        if ($sortField === 'subscription_status') {
            $query->leftJoin('subscriptions', 'users.id', '=', 'subscriptions.user_id')
                ->select('users.*')
                ->orderBy('subscriptions.stripe_status', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $perPage = $request->get('per_page', 25);
        $customers = $query->paginate($perPage)->withQueryString();

        // Add payment method details and subscription info to each customer
        $customers->getCollection()->transform(function ($customer) {
            $paymentMethod = $customer->defaultPaymentMethod();
            if ($paymentMethod) {
                $customer->pm_type = $paymentMethod->card->brand;
                $customer->pm_last_four = $paymentMethod->card->last4;
            }

            // Add tier and interval information
            if ($customer->subscriptions->isNotEmpty() && $customer->subscriptions->first()->stripe_price) {
                $subscription = $customer->subscriptions->first();
                $priceId = $subscription->stripe_price;

                // Sync subscription dates if missing
                if (! $subscription->current_period_end && $subscription->stripe_status === 'active' && ! str_starts_with($subscription->stripe_id, 'manual_')) {
                    try {
                        $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                        $stripeSubscription = $stripe->subscriptions->retrieve($subscription->stripe_id);

                        $subscription->current_period_start = $stripeSubscription->current_period_start ?
                            date('Y-m-d H:i:s', $stripeSubscription->current_period_start) : null;
                        $subscription->current_period_end = $stripeSubscription->current_period_end ?
                            date('Y-m-d H:i:s', $stripeSubscription->current_period_end) : null;

                        $subscription->save();
                    } catch (\Exception $e) {
                        // Log but don't fail
                        \Log::warning('Failed to sync subscription dates', ['subscription_id' => $subscription->id, 'error' => $e->getMessage()]);
                    }
                }

                // Try to get from StripeProduct table first
                $stripeProduct = \App\Models\StripeProduct::where('stripe_price_id', $priceId)
                    ->where('is_active', true)
                    ->first();

                if ($stripeProduct) {
                    $customer->tier = $stripeProduct->tier;
                    $customer->interval = ucfirst($stripeProduct->billing_period);
                } else {
                    // Fallback to parsing the price ID
                    $priceToTier = config('stripe.price_to_tier');
                    if (isset($priceToTier[$priceId])) {
                        $customer->tier = $priceToTier[$priceId]['tier'];
                        $customer->interval = ucfirst($priceToTier[$priceId]['period']);
                    }
                }
            }

            return $customer;
        });

        // Get statistics with a single optimized query
        $stats = SimpleCacheService::rememberQuery(
            SimpleCacheService::KEY_CUSTOMER_STATS,
            SimpleCacheService::TTL_SHORT,
            function () {
                $now = now()->toDateTimeString();

                return DB::selectOne('
                    SELECT 
                        COUNT(DISTINCT users.id) as total,
                        COUNT(DISTINCT CASE 
                            WHEN subscriptions.stripe_status = "active" 
                            THEN users.id 
                        END) as active,
                        COUNT(DISTINCT CASE 
                            WHEN subscriptions.trial_ends_at IS NOT NULL 
                            AND subscriptions.trial_ends_at > ? 
                            THEN users.id 
                        END) as trialing,
                        COUNT(DISTINCT CASE 
                            WHEN subscriptions.id IS NULL 
                            THEN users.id 
                        END) as no_subscription
                    FROM users
                    LEFT JOIN subscriptions ON users.id = subscriptions.user_id
                ', [$now]);
            }
        );

        return Inertia::render('admin/CustomersIndex', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'status', 'subscription_status', 'tier', 'start_date', 'end_date', 'sort', 'direction', 'per_page']),
            'stats' => $stats,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'action_type' => 'nullable|in:create,update,cancel,manual',
            'subscription_price' => 'nullable|string',
            'subscription_status' => 'nullable|string',
            'trial_days' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,disabled,pending',
        ]);

        // Handle user status change
        if (isset($data['status'])) {
            $user->update(['status' => $data['status']]);

            // Log the status change
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'old_status' => $user->getOriginal('status'),
                    'new_status' => $data['status'],
                ])
                ->log('Admin changed user status');

            // If just updating status, return early
            if (! isset($data['action_type'])) {
                return back()->with('success', 'User status updated successfully!');
            }
        }

        // Default to manual if action_type not provided
        $data['action_type'] = $data['action_type'] ?? 'manual';

        // Log admin action
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'action_type' => $data['action_type'],
                'subscription_price' => $data['subscription_price'] ?? null,
                'subscription_status' => $data['subscription_status'] ?? null,
                'trial_days' => $data['trial_days'] ?? null,
                'previous_status' => $user->subscriptions()->latest()->first()?->stripe_status,
                'has_payment_method' => $user->hasPaymentMethod(),
            ])
            ->log('Admin updated customer subscription');

        try {
            switch ($data['action_type']) {
                case 'create':
                    if (! $data['subscription_price']) {
                        return back()->withErrors(['subscription_price' => 'Plan is required for creating subscription']);
                    }

                    // Cancel any existing subscription
                    $current = $user->subscriptions()->active()->first();
                    if ($current) {
                        $current->cancelNow();
                    }

                    // Create new subscription
                    $builder = $user->newSubscription('default', $data['subscription_price']);

                    // Add trial if specified
                    if (! empty($data['trial_days'])) {
                        $builder->trialDays($data['trial_days']);
                    }

                    // Create subscription (will handle cases with/without payment method)
                    if ($user->hasPaymentMethod()) {
                        $builder->create();
                    } else {
                        // Create incomplete subscription that requires payment method
                        $builder->createWithoutPaymentMethod();
                    }

                    return back()->with('success', 'Subscription created successfully!'.
                        (! $user->hasPaymentMethod() ? ' Customer will need to add a payment method to activate billing.' : ''));

                case 'update':
                    if (! $data['subscription_price']) {
                        return back()->withErrors(['subscription_price' => 'Plan is required for updating subscription']);
                    }

                    $subscription = $user->subscriptions()->active()->first();
                    if (! $subscription) {
                        return back()->withErrors(['subscription' => 'No active subscription found to update']);
                    }

                    // Swap to new plan
                    $subscription->swap($data['subscription_price']);

                    return back()->with('success', 'Subscription plan updated! Changes will take effect at the next billing cycle.');

                case 'cancel':
                    $subscription = $user->subscriptions()->active()->first();
                    if (! $subscription) {
                        return back()->withErrors(['subscription' => 'No active subscription found to cancel']);
                    }

                    // Cancel at period end (graceful cancellation)
                    $subscription->cancel();

                    return back()->with('success', 'Subscription cancelled! Access will continue until '.
                        $subscription->ends_at->format('F j, Y'));

                case 'manual':
                    if (! $data['subscription_price']) {
                        return back()->withErrors(['subscription_price' => 'Plan is required for manual override']);
                    }

                    // Cancel any existing Stripe subscription
                    $current = $user->subscriptions()->active()->first();
                    if ($current) {
                        $current->cancelNow();
                    }

                    // Create manual subscription record
                    \DB::table('subscriptions')->insert([
                        'user_id' => $user->id,
                        'type' => 'default',
                        'stripe_id' => 'manual_'.uniqid(),
                        'stripe_status' => 'active',
                        'stripe_price' => $data['subscription_price'],
                        'quantity' => 1,
                        'trial_ends_at' => ! empty($data['trial_days']) ? now()->addDays($data['trial_days']) : null,
                        'ends_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    return back()->with('success', 'Manual subscription override applied! No automatic billing will occur.');
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update subscription: '.$e->getMessage()]);
        }

        return back()->with('error', 'Invalid action type');
    }

    /**
     * Send a password reset link to a customer
     */
    public function sendPasswordReset(Request $request, User $user)
    {
        // Log the action
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'user_email' => $user->email,
                'admin_email' => auth()->user()->email,
            ])
            ->log('Admin sent password reset to customer');

        // Generate password reset token and send email
        $status = Password::sendResetLink(
            ['email' => $user->email]
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Password reset link has been sent to '.$user->email);
        }

        return back()->with('error', 'Unable to send password reset link. Please try again.');
    }

    /**
     * Export customers to CSV
     */
    public function export(Request $request)
    {
        // Log the export action
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'filters' => $request->only(['search', 'status', 'tier', 'start_date', 'end_date']),
            ])
            ->log('Admin exported customer list');

        $query = User::with('subscriptions');

        // Apply the same filters as index
        if ($search = $request->get('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('id', 'like', '%'.$search.'%');
            });
        }

        if ($status = $request->get('status')) {
            if ($status === 'no_subscription') {
                $query->doesntHave('subscriptions');
            } else {
                $query->whereHas('subscriptions', function (Builder $q) use ($status) {
                    $q->where('stripe_status', $status);
                });
            }
        }

        $customers = $query->get();

        $csv = "ID,Name,Email,Status,Plan,Interval,Next Renewal,Created At\n";

        foreach ($customers as $customer) {
            $subscription = $customer->subscriptions->first();
            $status = $subscription ? $subscription->stripe_status : 'No Subscription';

            // Get tier and interval
            $tier = '-';
            $interval = '-';
            $nextRenewal = '-';

            if ($subscription && $subscription->stripe_price) {
                $priceId = $subscription->stripe_price;

                // Try to get from StripeProduct table first
                $stripeProduct = \App\Models\StripeProduct::where('stripe_price_id', $priceId)
                    ->where('is_active', true)
                    ->first();

                if ($stripeProduct) {
                    $tier = $stripeProduct->tier;
                    $interval = ucfirst($stripeProduct->billing_period);
                } else {
                    // Fallback to config
                    $priceToTier = config('stripe.price_to_tier');
                    if (isset($priceToTier[$priceId])) {
                        $tier = $priceToTier[$priceId]['tier'];
                        $interval = ucfirst($priceToTier[$priceId]['period']);
                    }
                }

                // Get next renewal date
                if ($subscription->current_period_end) {
                    $nextRenewal = \Carbon\Carbon::parse($subscription->current_period_end)->format('Y-m-d');
                }
            }

            $csv .= sprintf(
                "%d,\"%s\",\"%s\",%s,%s,%s,%s,%s\n",
                $customer->id,
                str_replace('"', '""', $customer->name),
                str_replace('"', '""', $customer->email),
                $status,
                $tier,
                $interval,
                $nextRenewal,
                $customer->created_at->format('Y-m-d H:i:s')
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers_export_'.date('Y-m-d_His').'.csv"',
        ]);
    }

    /**
     * Create a new customer account
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        // Create the user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        // Log the action
        // TODO: Add activity logging when spatie/laravel-activitylog is installed
        // activity()
        //     ->causedBy(auth()->user())
        //     ->performedOn($user)
        //     ->withProperties([
        //         'created_by' => auth()->user()->email,
        //         'user_email' => $user->email,
        //     ])
        //     ->log('Admin created new customer account');

        // Send welcome email if mail is configured
        try {
            if (config('mail.default') === 'log') {
                \Log::info('Welcome email for '.$user->email.' logged instead of sent (mail disabled)');
                $message = 'Customer account created successfully! (Email notifications are currently disabled)';
            } else {
                $user->sendEmailVerificationNotification();
                $message = 'Customer account created successfully! A welcome email has been sent to '.$user->email;
            }
        } catch (\Exception $e) {
            // If email fails, still consider the user created successfully
            \Log::warning('Failed to send welcome email to new customer: '.$e->getMessage());
            $message = 'Customer account created successfully! However, the welcome email could not be sent.';
        }

        return back()->with('success', $message);
    }

    /**
     * Cancel a customer's subscription
     */
    public function cancelSubscription(Request $request, User $user)
    {
        $immediately = $request->boolean('immediately', false);

        $subscription = $user->subscription('default');

        if (! $subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        try {
            if ($immediately) {
                $subscription->cancelNow();
                $message = 'Subscription cancelled immediately.';
            } else {
                $subscription->cancel();
                $message = 'Subscription will be cancelled at the end of the billing period.';
            }

            // Log the action
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'subscription_id' => $subscription->stripe_id,
                    'cancelled_immediately' => $immediately,
                    'ends_at' => $subscription->ends_at?->toDateTimeString(),
                ])
                ->log('Admin cancelled customer subscription');

            return back()->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('Subscription cancellation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to cancel subscription. Please try again.');
        }
    }
}
