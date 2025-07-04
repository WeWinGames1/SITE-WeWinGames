<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SimpleCacheService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

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
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('id', 'like', '%' . $search . '%');
            });
        }
        
        // Status filter
        if ($status = $request->get('status')) {
            if ($status === 'no_subscription') {
                $query->doesntHave('subscriptions');
            } else {
                $query->whereHas('subscriptions', function (Builder $q) use ($status) {
                    $q->where('stripe_status', $status);
                });
            }
        }
        
        // Tier filter (Silver, Gold, Platinum)
        if ($tier = $request->get('tier')) {
            $query->whereHas('subscriptions', function (Builder $q) use ($tier) {
                $q->where('stripe_price', 'like', '%' . strtolower($tier) . '%');
            });
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
        
        // Add payment method details to each customer
        $customers->getCollection()->transform(function ($customer) {
            $paymentMethod = $customer->defaultPaymentMethod();
            if ($paymentMethod) {
                $customer->pm_type = $paymentMethod->card->brand;
                $customer->pm_last_four = $paymentMethod->card->last4;
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
            'filters' => $request->only(['search', 'status', 'tier', 'start_date', 'end_date', 'sort', 'direction', 'per_page']),
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
        ]);
        
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
                    if (!$data['subscription_price']) {
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
                    if (!empty($data['trial_days'])) {
                        $builder->trialDays($data['trial_days']);
                    }
                    
                    // Create subscription (will handle cases with/without payment method)
                    if ($user->hasPaymentMethod()) {
                        $builder->create();
                    } else {
                        // Create incomplete subscription that requires payment method
                        $builder->createWithoutPaymentMethod();
                    }
                    
                    return back()->with('success', 'Subscription created successfully!' . 
                        (!$user->hasPaymentMethod() ? ' Customer will need to add a payment method to activate billing.' : ''));
                    
                case 'update':
                    if (!$data['subscription_price']) {
                        return back()->withErrors(['subscription_price' => 'Plan is required for updating subscription']);
                    }
                    
                    $subscription = $user->subscriptions()->active()->first();
                    if (!$subscription) {
                        return back()->withErrors(['subscription' => 'No active subscription found to update']);
                    }
                    
                    // Swap to new plan
                    $subscription->swap($data['subscription_price']);
                    
                    return back()->with('success', 'Subscription plan updated! Changes will take effect at the next billing cycle.');
                    
                case 'cancel':
                    $subscription = $user->subscriptions()->active()->first();
                    if (!$subscription) {
                        return back()->withErrors(['subscription' => 'No active subscription found to cancel']);
                    }
                    
                    // Cancel at period end (graceful cancellation)
                    $subscription->cancel();
                    
                    return back()->with('success', 'Subscription cancelled! Access will continue until ' . 
                        $subscription->ends_at->format('F j, Y'));
                    
                case 'manual':
                    if (!$data['subscription_price']) {
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
                        'stripe_id' => 'manual_' . uniqid(),
                        'stripe_status' => 'active',
                        'stripe_price' => $data['subscription_price'],
                        'quantity' => 1,
                        'trial_ends_at' => !empty($data['trial_days']) ? now()->addDays($data['trial_days']) : null,
                        'ends_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    return back()->with('success', 'Manual subscription override applied! No automatic billing will occur.');
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update subscription: ' . $e->getMessage()]);
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
            return back()->with('success', 'Password reset link has been sent to ' . $user->email);
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
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('id', 'like', '%' . $search . '%');
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
        
        $csv = "ID,Name,Email,Status,Plan,Trial Ends,Created At\n";
        
        foreach ($customers as $customer) {
            $subscription = $customer->subscriptions->first();
            $status = $subscription ? $subscription->stripe_status : 'No Subscription';
            $plan = $subscription && $subscription->price ? 
                str_replace('_', ' ', ucfirst(str_replace(['price_', '_monthly', '_weekly', '_daily'], '', $subscription->price))) : 
                '-';
            $trialEnds = $subscription && $subscription->trial_ends_at ? 
                $subscription->trial_ends_at->format('Y-m-d') : 
                '-';
            
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s\n",
                $customer->id,
                $customer->name,
                $customer->email,
                $status,
                $plan,
                $trialEnds,
                $customer->created_at->format('Y-m-d H:i:s')
            );
        }
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers_export_' . date('Y-m-d_His') . '.csv"',
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
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'created_by' => auth()->user()->email,
                'user_email' => $user->email,
            ])
            ->log('Admin created new customer account');
        
        // Send welcome email
        $user->sendEmailVerificationNotification();
        
        return back()->with('success', 'Customer account created successfully! A welcome email has been sent to ' . $user->email);
    }
}