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
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Display the specified customer details.
     */
    public function show(User $user)
    {
        // Load all necessary relationships
        $user->load([
            'subscriptions' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
        ]);

        // Get payment methods
        $paymentMethods = [];
        try {
            if ($user->hasPaymentMethod()) {
                foreach ($user->paymentMethods() as $method) {
                    // Check if card property exists
                    if (isset($method->card)) {
                        $paymentMethods[] = [
                            'id' => $method->id,
                            'brand' => $method->card->brand ?? 'Unknown',
                            'last4' => $method->card->last4 ?? '****',
                            'exp_month' => $method->card->exp_month ?? '',
                            'exp_year' => $method->card->exp_year ?? '',
                            'is_default' => $method->id === $user->defaultPaymentMethod()?->id,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch payment methods', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        // Get activity logs with eager loading to prevent N+1 queries
        $activities = collect([]);
        try {
            if (class_exists(\Spatie\Activitylog\Models\Activity::class) && Schema::hasTable('activity_log')) {
                $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', User::class)
                    ->where('subject_id', $user->id)
                    ->with('causer') // Eager load the causer relationship
                    ->orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get()
                    ->map(function ($activity) {
                        return [
                            'id' => $activity->id,
                            'description' => $activity->description,
                            'properties' => $activity->properties,
                            'causer' => $activity->causer ? [
                                'id' => $activity->causer->id,
                                'name' => $activity->causer->name,
                                'email' => $activity->causer->email,
                            ] : null,
                            'created_at' => $activity->created_at,
                        ];
                    });
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch activity logs', ['error' => $e->getMessage()]);
        }

        // Get subscription history
        $subscriptionHistory = $user->subscriptions()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($subscription) {
                $tier = null;
                $interval = null;

                // Get tier/interval from StripeProduct or config
                if ($subscription->stripe_price) {
                    $stripeProduct = \App\Models\StripeProduct::where('stripe_price_id', $subscription->stripe_price)
                        ->first();

                    if ($stripeProduct) {
                        $tier = $stripeProduct->tier;
                        $interval = $stripeProduct->billing_period;
                    } else {
                        $priceToTier = config('stripe.price_to_tier');
                        if (isset($priceToTier[$subscription->stripe_price])) {
                            $tier = $priceToTier[$subscription->stripe_price]['tier'];
                            $interval = $priceToTier[$subscription->stripe_price]['period'];
                        }
                    }
                }

                return [
                    'id' => $subscription->id,
                    'stripe_id' => $subscription->stripe_id,
                    'status' => $subscription->stripe_status,
                    'price_id' => $subscription->stripe_price,
                    'tier' => $tier,
                    'interval' => $interval,
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'current_period_start' => $subscription->current_period_start,
                    'current_period_end' => $subscription->current_period_end,
                    'ends_at' => $subscription->ends_at,
                    'created_at' => $subscription->created_at,
                    'is_manual' => str_starts_with($subscription->stripe_id, 'manual_'),
                ];
            });

        // Get current subscription details
        $currentSubscription = null;
        $activeSubscription = $user->subscription('default');
        if ($activeSubscription) {
            $currentSubscription = [
                'status' => $activeSubscription->stripe_status,
                'price_id' => $activeSubscription->stripe_price,
                'trial_ends_at' => $activeSubscription->trial_ends_at,
                'current_period_end' => $activeSubscription->current_period_end,
                'ends_at' => $activeSubscription->ends_at,
                'is_manual' => str_starts_with($activeSubscription->stripe_id, 'manual_'),
                'days_until_renewal' => $activeSubscription->current_period_end ?
                    (int) now()->diffInDays($activeSubscription->current_period_end, false) : null,
            ];
        }

        // Get invoices if available
        $invoices = collect([]); // Initialize as collection to ensure consistent type
        try {
            if ($user->stripe_id && ! str_starts_with($user->stripe_id, 'manual_')) {
                $invoices = $user->invoices()->take(10)->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'number' => $invoice->number,
                        'total' => $invoice->rawTotal(), // Use rawTotal() to get integer cents
                        'status' => $invoice->status,
                        'created_at' => $invoice->date()->toDateTimeString(),
                        'hosted_invoice_url' => $invoice->hosted_invoice_url,
                        'invoice_pdf' => $invoice->invoice_pdf,
                    ];
                });
            }
        } catch (\Exception $e) {
            // Log but don't fail
            \Log::warning('Failed to fetch invoices for user', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        // Get discount usage - check if tables exist first
        $discountUsage = collect([]);
        try {
            if (Schema::hasTable('discount_redemptions') && Schema::hasTable('discount_codes')) {
                $discountUsage = \DB::table('discount_redemptions')
                    ->join('discount_codes', 'discount_redemptions.discount_code_id', '=', 'discount_codes.id')
                    ->where('discount_redemptions.user_id', $user->id)
                    ->select([
                        'discount_codes.code',
                        'discount_codes.discount_amount as amount',
                        'discount_codes.discount_type as type',
                        'discount_redemptions.subscription_id',
                        'discount_redemptions.created_at',
                    ])
                    ->orderBy('discount_redemptions.created_at', 'desc')
                    ->get();
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch discount usage', ['error' => $e->getMessage()]);
        }

        return Inertia::render('admin/CustomerView', [
            'customer' => $user,
            'paymentMethods' => $paymentMethods,
            'activities' => $activities,
            'subscriptionHistory' => $subscriptionHistory,
            'currentSubscription' => $currentSubscription,
            'invoices' => $invoices,
            'discountUsage' => $discountUsage,
            'stats' => [
                'total_spent' => $invoices->sum('total') / 100, // Convert from cents
                'subscription_count' => $subscriptionHistory->count(),
                'days_as_customer' => $user->created_at->diffInDays(now()),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $query = User::with(['subscriptions' => function ($q) {
            $q->latest();
        }])
            ->withCount('subscriptions');

        // Debug: Log the initial request
        \Log::info('CustomerController index request', [
            'all_params' => $request->all(),
            'tier' => $request->get('tier'),
            'subscription_status' => $request->get('subscription_status'),
        ]);

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
            \Log::info('Applying subscription status filter', ['status' => $subscriptionStatus]);

            if ($subscriptionStatus === 'no_subscription') {
                $query->whereDoesntHave('subscriptions', function (Builder $q) {
                    $q->whereIn('stripe_status', ['active', 'trialing']);
                });
            } else {
                $query->whereHas('subscriptions', function (Builder $q) use ($subscriptionStatus) {
                    $q->where('stripe_status', $subscriptionStatus)
                        ->whereNull('ends_at'); // Ensure it's not cancelled
                });
            }
        }

        // Tier filter (Free, Silver, Gold, Platinum)
        if ($tier = $request->get('tier')) {
            \Log::info('Applying tier filter', ['tier' => $tier]);

            if ($tier === 'free') {
                // Free users have no active subscriptions AND no admin override
                $query->whereDoesntHave('subscriptions', function (Builder $q) {
                    $q->whereIn('stripe_status', ['active', 'trialing']);
                })->where(function ($q) {
                    $q->where('admin_override', '!=', true)
                        ->orWhereNull('admin_override');
                });
            } elseif ($tier !== 'all') {
                // Normalize tier name to proper case (Silver, Gold, Platinum)
                $normalizedTier = ucfirst(strtolower($tier));

                // Get all price IDs for this tier from StripeProduct table
                $priceIds = \App\Models\StripeProduct::where('tier', $normalizedTier)
                    ->where('is_active', true)
                    ->pluck('stripe_price_id')
                    ->filter()
                    ->values()
                    ->toArray();

                // Also check for legacy price IDs from config
                $priceToTier = config('stripe.price_to_tier', []);
                foreach ($priceToTier as $priceId => $tierInfo) {
                    if (isset($tierInfo['tier']) && $tierInfo['tier'] === $normalizedTier) {
                        $priceIds[] = $priceId;
                    }
                }

                // Add legacy hardcoded price IDs that might be in the database
                $legacyPriceIds = [
                    'Silver' => ['price_silver_monthly', 'price_silver_weekly', 'price_silver_daily'],
                    'Gold' => ['price_gold_monthly', 'price_gold_weekly', 'price_gold_daily'],
                    'Platinum' => ['price_platinum_monthly', 'price_platinum_weekly', 'price_platinum_daily'],
                ];

                if (isset($legacyPriceIds[$normalizedTier])) {
                    $priceIds = array_merge($priceIds, $legacyPriceIds[$normalizedTier]);
                }

                // Remove duplicates and ensure unique values
                $priceIds = array_values(array_unique(array_filter($priceIds)));

                \Log::info('Tier filter price IDs', [
                    'tier' => $tier,
                    'normalized_tier' => $normalizedTier,
                    'price_ids_count' => count($priceIds),
                    'price_ids' => $priceIds,
                ]);

                // Apply the filter - users must have EXACTLY this tier
                $query->where(function ($mainQuery) use ($priceIds, $normalizedTier) {
                    // Option 1: Has active subscription with this tier's price ID
                    $mainQuery->where(function ($subQuery) use ($priceIds) {
                        if (! empty($priceIds)) {
                            $subQuery->whereHas('subscriptions', function (Builder $q) use ($priceIds) {
                                $q->whereIn('stripe_price', $priceIds)
                                    ->whereIn('stripe_status', ['active', 'trialing'])
                                    ->whereNull('ends_at');
                            });
                        }
                    })
                    // Option 2: OR has admin override for this tier
                        ->orWhere(function ($overrideQuery) use ($normalizedTier) {
                            $overrideQuery->where('admin_override', true)
                                ->where('override_tier', $normalizedTier);
                        });
                });
            }
            // If tier === 'all', don't add any tier filtering
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
            // Use a subquery to get the latest subscription status for ordering
            $query->orderBy(
                \Laravel\Cashier\Subscription::select('stripe_status')
                    ->whereColumn('user_id', 'users.id')
                    ->orderBy('created_at', 'desc')
                    ->limit(1),
                $sortDirection
            );
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

                    // Cancel any existing subscription (including manual ones)
                    $existingSubscriptions = $user->subscriptions()->whereNull('ends_at')->get();
                    foreach ($existingSubscriptions as $subscription) {
                        $subscription->cancelNow();
                    }

                    // Clear any admin override fields
                    $user->update([
                        'admin_override' => false,
                        'override_tier' => null,
                        'override_expiry' => null,
                    ]);

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

                    // Get subscription that's not cancelled
                    $subscription = $user->subscriptions()
                        ->whereNull('ends_at')
                        ->whereIn('stripe_status', ['active', 'trialing'])
                        ->first();

                    if (! $subscription) {
                        return back()->withErrors(['subscription' => 'No active subscription found to update']);
                    }

                    // Check if it's a manual subscription
                    if (str_starts_with($subscription->stripe_id, 'manual_')) {
                        // For manual subscriptions, we need to cancel and create a new one
                        $subscription->cancelNow();

                        // Create new subscription
                        $builder = $user->newSubscription('default', $data['subscription_price']);

                        // Add trial if specified
                        if (! empty($data['trial_days'])) {
                            $builder->trialDays($data['trial_days']);
                        }

                        // Create subscription
                        if ($user->hasPaymentMethod()) {
                            $builder->create();
                        } else {
                            $builder->createWithoutPaymentMethod();
                        }

                        return back()->with('success', 'Subscription updated successfully!'.
                            (! $user->hasPaymentMethod() ? ' Customer will need to add a payment method to activate billing.' : ''));
                    } else {
                        // For regular Stripe subscriptions, use swap
                        $subscription->swap($data['subscription_price']);

                        return back()->with('success', 'Subscription plan updated! Changes will take effect at the next billing cycle.');
                    }

                case 'cancel':
                    // Get subscription that's not already cancelled
                    $subscription = $user->subscriptions()
                        ->whereNull('ends_at')
                        ->whereIn('stripe_status', ['active', 'trialing'])
                        ->first();

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

                    // Wrap in database transaction for data integrity
                    \DB::transaction(function () use ($user, $data) {
                        // Cancel any existing subscriptions (including manual ones)
                        $existingSubscriptions = $user->subscriptions()->whereNull('ends_at')->get();
                        foreach ($existingSubscriptions as $subscription) {
                            $subscription->cancelNow();
                        }

                        // Parse price to get tier information
                        $tier = null;
                        $billingPeriod = 'monthly'; // default

                        // Try to get from StripeProduct table first
                        $stripeProduct = \App\Models\StripeProduct::where('stripe_price_id', $data['subscription_price'])
                            ->where('is_active', true)
                            ->first();

                        if ($stripeProduct) {
                            $tier = $stripeProduct->tier;
                            $billingPeriod = $stripeProduct->billing_period;
                        } else {
                            // Fallback to config
                            $priceToTier = config('stripe.price_to_tier');
                            if (isset($priceToTier[$data['subscription_price']])) {
                                $tier = $priceToTier[$data['subscription_price']]['tier'];
                                $billingPeriod = $priceToTier[$data['subscription_price']]['period'];
                            }
                        }

                        // Calculate period end based on billing period
                        $periodEnd = now();
                        switch ($billingPeriod) {
                            case 'daily':
                                $periodEnd->addDay();
                                break;
                            case 'weekly':
                                $periodEnd->addWeek();
                                break;
                            case 'yearly':
                                $periodEnd->addYear();
                                break;
                            default: // monthly
                                $periodEnd->addMonth();
                                break;
                        }

                        // Create manual subscription record using Laravel Cashier's Subscription model
                        $subscription = $user->subscriptions()->create([
                            'type' => 'default',
                            'stripe_id' => 'manual_'.uniqid(),
                            'stripe_status' => 'active',
                            'stripe_price' => $data['subscription_price'],
                            'quantity' => 1,
                            'trial_ends_at' => ! empty($data['trial_days']) ? now()->addDays($data['trial_days']) : null,
                            'current_period_start' => now(),
                            'current_period_end' => $periodEnd,
                            'ends_at' => null,
                        ]);

                        // Update user override fields
                        if ($tier) {
                            $user->update([
                                'admin_override' => true,
                                'override_tier' => $tier,
                                'override_expiry' => $periodEnd->toDateString(),
                            ]);
                        }
                    });

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
     * Show the form for creating a new customer
     */
    public function showCreateForm()
    {
        return Inertia::render('admin/CustomerCreate');
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

        // Get subscription that's not already cancelled
        $subscription = $user->subscriptions()
            ->where('type', 'default')
            ->whereNull('ends_at')
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->first();

        if (! $subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        try {
            if ($immediately) {
                $subscription->cancelNow();

                // Clear any admin override fields when cancelling immediately
                $user->update([
                    'admin_override' => false,
                    'override_tier' => null,
                    'override_expiry' => null,
                ]);

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

    /**
     * Search customers for API
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['customers' => []]);
        }

        $customers = User::where(function ($q) use ($query) {
            $q->where('name', 'like', '%'.$query.'%')
                ->orWhere('email', 'like', '%'.$query.'%');
        })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json(['customers' => $customers]);
    }
}
