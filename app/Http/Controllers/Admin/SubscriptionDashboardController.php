<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StripeProduct;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Laravel\Cashier\Subscription;

class SubscriptionDashboardController extends Controller
{
    /**
     * Display the subscription dashboard
     */
    public function index(Request $request)
    {
        // Get filters
        $filters = $request->only(['status', 'tier', 'renewal_period', 'search']);
        
        // Build query
        $query = User::with(['subscriptions', 'subscriptions.items'])
            ->whereHas('subscriptions');
        
        // Apply search filter
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }
        
        // Apply status filter
        if ($request->filled('status')) {
            $query->whereHas('subscriptions', function ($q) use ($request) {
                $q->where('stripe_status', $request->status);
            });
        }
        
        // Apply tier filter
        if ($request->filled('tier')) {
            $tierPriceIds = StripeProduct::where('tier', $request->tier)
                ->pluck('stripe_price_id')
                ->filter()
                ->toArray();
                
            $query->whereHas('subscriptions.items', function ($q) use ($tierPriceIds) {
                $q->whereIn('stripe_price', $tierPriceIds);
            });
        }
        
        // Apply renewal period filter
        if ($request->filled('renewal_period')) {
            $days = intval($request->renewal_period);
            $startDate = Carbon::now();
            $endDate = Carbon::now()->addDays($days);
            
            $query->whereHas('subscriptions', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('current_period_end', [$startDate, $endDate]);
            });
        }
        
        // Get paginated results
        $customers = $query->paginate(50)->withQueryString();
        
        // Transform data for frontend
        $customers->through(function ($user) {
            $subscription = $user->subscriptions->first();
            $tier = $this->getSubscriptionTier($subscription);
            
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'subscription_id' => $subscription->id,
                'status' => $subscription->stripe_status,
                'tier' => $tier,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'created_at' => $subscription->created_at,
                'is_ambassador' => $user->is_ambassador,
                'is_gifted' => $user->is_gifted,
                'has_override' => $user->admin_override,
                'days_until_renewal' => $subscription->current_period_end 
                    ? max(0, Carbon::now()->diffInDays(Carbon::parse($subscription->current_period_end), false))
                    : null,
            ];
        });
        
        // Get statistics
        $stats = $this->getSubscriptionStats();
        
        return Inertia::render('admin/Subscriptions/Dashboard', [
            'customers' => $customers,
            'filters' => $filters,
            'stats' => $stats,
            'tiers' => ['Bronze', 'Silver', 'Gold', 'Platinum'],
        ]);
    }
    
    /**
     * Export subscriptions to CSV
     */
    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        
        $query = User::with(['subscriptions', 'subscriptions.items']);
        
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            $query->whereHas('subscriptions');
        }
        
        $users = $query->get();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="subscriptions_export_' . now()->format('Y-m-d') . '.csv"',
        ];
        
        $callback = function() use ($users) {
            $handle = fopen('php://output', 'w');
            
            // CSV header
            fputcsv($handle, [
                'Name', 'Email', 'Tier', 'Status', 'Started', 'Renews', 
                'Days Until Renewal', 'Is Ambassador', 'Is Gifted'
            ]);
            
            foreach ($users as $user) {
                $subscription = $user->subscriptions->first();
                if (!$subscription) continue;
                
                $tier = $this->getSubscriptionTier($subscription);
                
                fputcsv($handle, [
                    $user->name,
                    $user->email,
                    $tier,
                    $subscription->stripe_status,
                    $subscription->created_at->format('Y-m-d'),
                    Carbon::parse($subscription->current_period_end)->format('Y-m-d'),
                    $subscription->current_period_end 
                        ? max(0, Carbon::now()->diffInDays(Carbon::parse($subscription->current_period_end), false))
                        : 'N/A',
                    $user->is_ambassador ? 'Yes' : 'No',
                    $user->is_gifted ? 'Yes' : 'No',
                ]);
            }
            
            fclose($handle);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Grant a manual subscription to a user
     */
    public function grantSubscription(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tier' => 'required|in:Bronze,Silver,Gold,Platinum',
            'duration_days' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);
        
        $user = User::find($validated['user_id']);
        
        // Set override fields
        $user->update([
            'admin_override' => true,
            'override_tier' => $validated['tier'],
            'override_expiry' => Carbon::now()->addDays($validated['duration_days']),
        ]);
        
        // Log the action
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'tier' => $validated['tier'],
                'duration_days' => $validated['duration_days'],
                'reason' => $validated['reason'],
            ])
            ->log('Granted manual subscription');
        
        return response()->json([
            'success' => true,
            'message' => 'Subscription granted successfully',
        ]);
    }
    
    /**
     * Cancel a user's subscription
     */
    public function cancelSubscription(Request $request, User $user)
    {
        $subscription = $user->subscription();
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'User has no active subscription',
            ], 404);
        }
        
        try {
            if ($request->input('immediately', false)) {
                $subscription->cancelNow();
            } else {
                $subscription->cancel();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get subscription statistics
     */
    private function getSubscriptionStats(): array
    {
        $activeSubscriptions = Subscription::where('stripe_status', 'active')->count();
        $trialingSubscriptions = Subscription::where('stripe_status', 'trialing')->count();
        $cancelledSubscriptions = Subscription::where('stripe_status', 'canceled')->count();
        
        // Get tier breakdown
        $tierBreakdown = DB::table('subscriptions')
            ->join('subscription_items', 'subscriptions.id', '=', 'subscription_items.subscription_id')
            ->join('stripe_products', 'subscription_items.stripe_price', '=', 'stripe_products.stripe_price_id')
            ->where('subscriptions.stripe_status', 'active')
            ->select('stripe_products.tier', DB::raw('count(*) as count'))
            ->groupBy('stripe_products.tier')
            ->pluck('count', 'tier')
            ->toArray();
        
        // Get MRR (Monthly Recurring Revenue)
        $mrr = DB::table('subscriptions')
            ->join('subscription_items', 'subscriptions.id', '=', 'subscription_items.subscription_id')
            ->join('stripe_products', 'subscription_items.stripe_price', '=', 'stripe_products.stripe_price_id')
            ->where('subscriptions.stripe_status', 'active')
            ->select(DB::raw('SUM(
                CASE 
                    WHEN stripe_products.billing_period = "monthly" THEN stripe_products.price
                    WHEN stripe_products.billing_period = "weekly" THEN stripe_products.price * 4.33
                    WHEN stripe_products.billing_period = "daily" THEN stripe_products.price * 30
                    ELSE 0
                END
            ) as mrr'))
            ->first()
            ->mrr ?? 0;
        
        // Get renewals in next 30 days
        $upcomingRenewals = Subscription::where('stripe_status', 'active')
            ->whereBetween('current_period_end', [Carbon::now(), Carbon::now()->addDays(30)])
            ->count();
        
        return [
            'total_active' => $activeSubscriptions,
            'total_trialing' => $trialingSubscriptions,
            'total_cancelled' => $cancelledSubscriptions,
            'tier_breakdown' => $tierBreakdown,
            'mrr' => round($mrr, 2),
            'upcoming_renewals' => $upcomingRenewals,
        ];
    }
    
    /**
     * Get subscription tier from subscription
     */
    private function getSubscriptionTier($subscription): ?string
    {
        if (!$subscription || !$subscription->items->first()) {
            return null;
        }
        
        $priceId = $subscription->items->first()->stripe_price;
        $product = StripeProduct::where('stripe_price_id', $priceId)->first();
        
        return $product ? $product->tier : 'Unknown';
    }
}
