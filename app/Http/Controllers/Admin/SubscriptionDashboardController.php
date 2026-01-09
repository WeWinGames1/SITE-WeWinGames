<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeProduct;
use App\Models\User;
use App\Services\SimpleCacheService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
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
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
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

        // Pre-load all StripeProducts to avoid N+1 queries
        $priceIds = [];
        foreach ($customers as $user) {
            $subscription = $user->subscriptions->first();
            if ($subscription && $subscription->items->first()) {
                $priceIds[] = $subscription->items->first()->stripe_price;
            }
        }

        // Load all products in one query and create a lookup map
        $productMap = StripeProduct::whereIn('stripe_price_id', array_unique($priceIds))
            ->pluck('tier', 'stripe_price_id')
            ->toArray();

        // Transform data for frontend
        $customers->through(function ($user) use ($productMap) {
            $subscription = $user->subscriptions->first();

            // Get tier from pre-loaded map
            $tier = null;
            if ($subscription && $subscription->items->first()) {
                $priceId = $subscription->items->first()->stripe_price;
                $tier = $productMap[$priceId] ?? 'Unknown';
            }

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
            'tiers' => ['Free', 'Gold', 'Platinum'],
        ]);
    }

    /**
     * Export subscriptions to CSV
     */
    public function export(Request $request)
    {
        $ids = $request->input('ids', []);

        $query = User::with(['subscriptions', 'subscriptions.items']);

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            $query->whereHas('subscriptions');
        }

        $users = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="subscriptions_export_'.now()->format('Y-m-d').'.csv"',
        ];

        $callback = function () use ($users) {
            $handle = fopen('php://output', 'w');

            // CSV header
            fputcsv($handle, [
                'Name', 'Email', 'Tier', 'Status', 'Started', 'Renews',
                'Days Until Renewal', 'Is Ambassador', 'Is Gifted',
            ]);

            foreach ($users as $user) {
                $subscription = $user->subscriptions->first();
                if (! $subscription) {
                    continue;
                }

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
            'tier' => 'required|in:Free,Gold,Platinum',
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

        if (! $subscription) {
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
                'message' => 'Failed to cancel subscription: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get subscription statistics
     */
    private function getSubscriptionStats(): array
    {
        return SimpleCacheService::rememberQuery(
            SimpleCacheService::KEY_SUBSCRIPTION_STATS,
            SimpleCacheService::TTL_SHORT,
            function () {
                // Get all stats in a single optimized query
                $now = now()->toDateTimeString();
                $thirtyDaysFromNow = now()->addDays(30)->toDateTimeString();

                $baseStats = DB::selectOne('
                    SELECT 
                        COUNT(CASE WHEN stripe_status = "active" THEN 1 END) as active,
                        COUNT(CASE WHEN stripe_status = "trialing" THEN 1 END) as trialing,
                        COUNT(CASE WHEN stripe_status = "canceled" THEN 1 END) as cancelled,
                        COUNT(CASE WHEN stripe_status = "active" AND current_period_end BETWEEN ? AND ? THEN 1 END) as renewals_30_days
                    FROM subscriptions
                ', [$now, $thirtyDaysFromNow]);

                // Get tier breakdown and MRR in one query
                $tierData = DB::select('
                    SELECT 
                        sp.tier,
                        COUNT(*) as count,
                        SUM(
                            CASE 
                                WHEN sp.billing_period = "monthly" THEN sp.price
                                WHEN sp.billing_period = "weekly" THEN sp.price * 4.33
                                WHEN sp.billing_period = "daily" THEN sp.price * 30
                                ELSE 0
                            END
                        ) as revenue
                    FROM subscriptions s
                    JOIN subscription_items si ON s.id = si.subscription_id
                    JOIN stripe_products sp ON si.stripe_price = sp.stripe_price_id
                    WHERE s.stripe_status = "active"
                    GROUP BY sp.tier
                ');

                $tierBreakdown = [];
                $totalMrr = 0;

                foreach ($tierData as $tier) {
                    $tierBreakdown[$tier->tier] = $tier->count;
                    $totalMrr += $tier->revenue;
                }

                return [
                    'total_active' => $baseStats->active,
                    'total_trialing' => $baseStats->trialing,
                    'total_cancelled' => $baseStats->cancelled,
                    'tier_breakdown' => $tierBreakdown,
                    'mrr' => round($totalMrr, 2),
                    'upcoming_renewals' => $baseStats->renewals_30_days,
                ];
            }
        );
    }

    /**
     * Get subscription tier from subscription
     */
    private function getSubscriptionTier($subscription): ?string
    {
        if (! $subscription || ! $subscription->items->first()) {
            return null;
        }

        $priceId = $subscription->items->first()->stripe_price;
        $product = StripeProduct::where('stripe_price_id', $priceId)->first();

        return $product ? $product->tier : 'Unknown';
    }
}
