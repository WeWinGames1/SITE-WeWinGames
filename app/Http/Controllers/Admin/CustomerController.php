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
            'subscription_price' => 'nullable|string',
            'subscription_status' => 'required|string',
            'trial_days' => 'nullable|integer|min:0',
        ]);

        // Log admin action
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'subscription_price' => $data['subscription_price'] ?? null,
                'subscription_status' => $data['subscription_status'],
                'trial_days' => $data['trial_days'] ?? null,
                'previous_status' => $user->subscriptions()->latest()->first()?->stripe_status,
            ])
            ->log('Admin updated customer subscription');

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