<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerValueReportController extends Controller
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Display the customer value reports page.
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $affiliateId = $request->input('affiliate_id');
        $discountCode = $request->input('discount_code');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Generate cache key based on filters
        $cacheKey = 'customer_value_report_'.md5(json_encode([
            $affiliateId,
            $discountCode,
            $dateFrom,
            $dateTo,
        ]));

        // Try to get from cache, or compute
        $reportData = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($affiliateId, $discountCode, $dateFrom, $dateTo) {
            return $this->generateReportData($affiliateId, $discountCode, $dateFrom, $dateTo);
        });

        // Get available filters (these can be cached longer)
        $affiliates = Cache::remember('report_affiliates_list', 600, function () {
            return Affiliate::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
        });

        $discountCodes = Cache::remember('report_discount_codes_list', 600, function () {
            return DiscountCode::where('is_active', true)
                ->orderBy('code')
                ->pluck('code')
                ->toArray();
        });

        return Inertia::render('admin/Reports/CustomerValue', [
            'metrics' => $reportData['metrics'],
            'affiliatePerformance' => $reportData['affiliatePerformance'],
            'discountCodePerformance' => $reportData['discountCodePerformance'],
            'tierTrends' => $reportData['tierTrends'],
            'affiliates' => $affiliates,
            'discountCodes' => $discountCodes,
            'filters' => [
                'affiliate_id' => $affiliateId,
                'discount_code' => $discountCode,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * Generate all report data.
     */
    private function generateReportData(?string $affiliateId, ?string $discountCode, ?string $dateFrom, ?string $dateTo): array
    {
        // Build base query for users
        $usersQuery = User::query();

        // Apply affiliate filter
        if ($affiliateId) {
            $usersQuery->where('affiliate_id', $affiliateId);
        }

        // Apply discount code filter (users who have used this discount code)
        if ($discountCode) {
            $usersQuery->whereHas('discountRedemptions', function ($query) use ($discountCode) {
                $query->whereHas('discountCode', function ($q) use ($discountCode) {
                    $q->where('code', $discountCode);
                });
            });
        }

        // Apply date filters
        if ($dateFrom) {
            $usersQuery->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $usersQuery->where('created_at', '<=', $dateTo.' 23:59:59');
        }

        // Get user IDs for filtered users
        $userIds = $usersQuery->pluck('id')->toArray();

        return [
            'metrics' => $this->calculateMetrics($userIds, $dateFrom, $dateTo),
            'affiliatePerformance' => $this->getAffiliatePerformance($dateFrom, $dateTo),
            'discountCodePerformance' => $this->getDiscountCodePerformance($dateFrom, $dateTo),
            'tierTrends' => $this->getTierTrends($userIds),
        ];
    }

    /**
     * Calculate main metrics for the report.
     */
    private function calculateMetrics(array $userIds, ?string $dateFrom, ?string $dateTo): array
    {
        $totalCustomers = count($userIds);

        if ($totalCustomers === 0) {
            return $this->getEmptyMetrics();
        }

        // Use a single query to get multiple subscription stats
        $subscriptionStats = DB::table('subscriptions')
            ->whereIn('user_id', $userIds)
            ->selectRaw('
                COUNT(DISTINCT CASE WHEN stripe_status = ? THEN user_id END) as active_subscribers,
                COUNT(DISTINCT CASE WHEN ends_at IS NOT NULL THEN user_id END) as cancelled_users
            ', ['active'])
            ->first();

        $activeSubscribers = $subscriptionStats->active_subscribers ?? 0;

        // Cancelled count with date filters
        $cancelledQuery = DB::table('subscriptions')
            ->whereIn('user_id', $userIds)
            ->whereNotNull('ends_at');

        if ($dateFrom) {
            $cancelledQuery->where('ends_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $cancelledQuery->where('ends_at', '<=', $dateTo.' 23:59:59');
        }

        $cancelledCount = $cancelledQuery->distinct('user_id')->count('user_id');

        // Resubscriptions - optimized single query
        $resubscribeCount = $this->countResubscriptions($userIds);

        // Tier changes - optimized batch query
        [$upgradeCount, $downgradeCount] = $this->countTierChangesOptimized($userIds);

        // Average subscription duration - database agnostic
        $avgDuration = $this->calculateAvgSubscriptionDuration($userIds);

        // Revenue calculation
        $revenueData = $this->calculateRevenue($userIds);
        $totalRevenue = $revenueData['total'];
        $avgLtv = $totalCustomers > 0 ? ($totalRevenue / $totalCustomers) : 0;

        // Calculate rates
        $cancellationRate = $totalCustomers > 0 ? round(($cancelledCount / $totalCustomers) * 100, 2) : 0;
        $resubscribeRate = $cancelledCount > 0 ? round(($resubscribeCount / $cancelledCount) * 100, 2) : 0;
        $upgradeRate = $totalCustomers > 0 ? round(($upgradeCount / $totalCustomers) * 100, 2) : 0;
        $downgradeRate = $totalCustomers > 0 ? round(($downgradeCount / $totalCustomers) * 100, 2) : 0;

        return [
            'total_customers' => $totalCustomers,
            'active_subscribers' => $activeSubscribers,
            'cancelled_count' => $cancelledCount,
            'cancellation_rate' => $cancellationRate,
            'resubscribe_count' => $resubscribeCount,
            'resubscribe_rate' => $resubscribeRate,
            'upgrade_count' => $upgradeCount,
            'upgrade_rate' => $upgradeRate,
            'downgrade_count' => $downgradeCount,
            'downgrade_rate' => $downgradeRate,
            'avg_subscription_duration_days' => round($avgDuration, 1),
            'total_revenue' => round($totalRevenue, 2),
            'avg_ltv' => round($avgLtv, 2),
        ];
    }

    /**
     * Get empty metrics array.
     */
    private function getEmptyMetrics(): array
    {
        return [
            'total_customers' => 0,
            'active_subscribers' => 0,
            'cancelled_count' => 0,
            'cancellation_rate' => 0,
            'resubscribe_count' => 0,
            'resubscribe_rate' => 0,
            'upgrade_count' => 0,
            'upgrade_rate' => 0,
            'downgrade_count' => 0,
            'downgrade_rate' => 0,
            'avg_subscription_duration_days' => 0,
            'total_revenue' => 0,
            'avg_ltv' => 0,
        ];
    }

    /**
     * Count resubscriptions using an optimized query.
     */
    private function countResubscriptions(array $userIds): int
    {
        if (empty($userIds)) {
            return 0;
        }

        // Find users who have both a cancelled subscription and a later active subscription
        return DB::table('subscriptions as cancelled')
            ->whereIn('cancelled.user_id', $userIds)
            ->whereNotNull('cancelled.ends_at')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('subscriptions as resubscribed')
                    ->whereColumn('resubscribed.user_id', 'cancelled.user_id')
                    ->where('resubscribed.stripe_status', 'active')
                    ->whereColumn('resubscribed.created_at', '>', 'cancelled.ends_at');
            })
            ->distinct('cancelled.user_id')
            ->count('cancelled.user_id');
    }

    /**
     * Count tier changes (upgrades and downgrades) using optimized batch query.
     *
     * @return array{0: int, 1: int} [upgradeCount, downgradeCount]
     */
    private function countTierChangesOptimized(array $userIds): array
    {
        if (empty($userIds)) {
            return [0, 0];
        }

        // Get all subscriptions with tiers for filtered users in one query
        $subscriptions = DB::table('subscriptions')
            ->leftJoin('stripe_products', 'subscriptions.stripe_price', '=', 'stripe_products.stripe_price_id')
            ->whereIn('subscriptions.user_id', $userIds)
            ->orderBy('subscriptions.user_id')
            ->orderBy('subscriptions.created_at')
            ->select('subscriptions.user_id', 'stripe_products.tier')
            ->get();

        if ($subscriptions->isEmpty()) {
            return [0, 0];
        }

        // Tier order for comparison
        $tierOrder = [
            'Free' => 1,
            'Bronze' => 1,
            'Silver' => 1,
            'Gold' => 2,
            'Platinum' => 3,
        ];

        $upgradeCount = 0;
        $downgradeCount = 0;
        $userUpgraded = [];
        $userDowngraded = [];

        $currentUserId = null;
        $previousTierLevel = null;

        foreach ($subscriptions as $sub) {
            // Reset when moving to a new user
            if ($currentUserId !== $sub->user_id) {
                $currentUserId = $sub->user_id;
                $previousTierLevel = null;
            }

            $currentTier = $sub->tier ?? 'Free';
            $currentTierLevel = $tierOrder[$currentTier] ?? 1;

            if ($previousTierLevel !== null) {
                // Check for upgrade (only count once per user)
                if ($currentTierLevel > $previousTierLevel && ! isset($userUpgraded[$currentUserId])) {
                    $upgradeCount++;
                    $userUpgraded[$currentUserId] = true;
                }
                // Check for downgrade (only count once per user)
                if ($currentTierLevel < $previousTierLevel && ! isset($userDowngraded[$currentUserId])) {
                    $downgradeCount++;
                    $userDowngraded[$currentUserId] = true;
                }
            }

            $previousTierLevel = $currentTierLevel;
        }

        return [$upgradeCount, $downgradeCount];
    }

    /**
     * Calculate average subscription duration in a database-agnostic way.
     */
    private function calculateAvgSubscriptionDuration(array $userIds): float
    {
        if (empty($userIds)) {
            return 0;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $avgDays = DB::table('subscriptions')
                ->whereIn('user_id', $userIds)
                ->whereNotNull('ends_at')
                ->selectRaw('AVG(JULIANDAY(ends_at) - JULIANDAY(created_at)) as avg_days')
                ->value('avg_days');
        } else {
            // MySQL / PostgreSQL
            $avgDays = DB::table('subscriptions')
                ->whereIn('user_id', $userIds)
                ->whereNotNull('ends_at')
                ->selectRaw('AVG(DATEDIFF(ends_at, created_at)) as avg_days')
                ->value('avg_days');
        }

        return (float) ($avgDays ?? 0);
    }

    /**
     * Calculate revenue from active subscriptions.
     */
    private function calculateRevenue(array $userIds): array
    {
        if (empty($userIds)) {
            return ['total' => 0, 'by_tier' => []];
        }

        $revenueByTier = DB::table('subscriptions')
            ->join('stripe_products', 'subscriptions.stripe_price', '=', 'stripe_products.stripe_price_id')
            ->whereIn('subscriptions.user_id', $userIds)
            ->where('subscriptions.stripe_status', 'active')
            ->groupBy('stripe_products.tier')
            ->select('stripe_products.tier', DB::raw('COUNT(*) as count'), DB::raw('SUM(stripe_products.price) as revenue'))
            ->get();

        return [
            'total' => $revenueByTier->sum('revenue'),
            'by_tier' => $revenueByTier->pluck('revenue', 'tier')->toArray(),
        ];
    }

    /**
     * Get affiliate performance metrics.
     */
    private function getAffiliatePerformance(?string $dateFrom, ?string $dateTo): array
    {
        // Build date constraint closure
        $dateConstraint = function ($query) use ($dateFrom, $dateTo) {
            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo.' 23:59:59');
            }
        };

        $affiliates = Affiliate::where('is_active', true)
            ->withCount(['users as total_customers' => $dateConstraint])
            ->withCount(['users as active_subscribers' => function ($query) use ($dateConstraint) {
                $dateConstraint($query);
                $query->whereHas('subscriptions', function ($q) {
                    $q->where('stripe_status', 'active');
                });
            }])
            ->withCount(['users as cancelled_customers' => function ($query) use ($dateConstraint) {
                $dateConstraint($query);
                $query->whereHas('subscriptions', function ($q) {
                    $q->whereNotNull('ends_at');
                });
            }])
            ->having('total_customers', '>', 0)
            ->orderByDesc('total_customers')
            ->limit(10)
            ->get();

        return $affiliates->map(function ($affiliate) {
            $cancellationRate = $affiliate->total_customers > 0
                ? round(($affiliate->cancelled_customers / $affiliate->total_customers) * 100, 2)
                : 0;

            $conversionRate = $affiliate->total_customers > 0
                ? round(($affiliate->active_subscribers / $affiliate->total_customers) * 100, 2)
                : 0;

            return [
                'id' => $affiliate->id,
                'name' => $affiliate->name,
                'code' => $affiliate->code,
                'total_customers' => $affiliate->total_customers,
                'active_subscribers' => $affiliate->active_subscribers,
                'cancelled_customers' => $affiliate->cancelled_customers,
                'cancellation_rate' => $cancellationRate,
                'conversion_rate' => $conversionRate,
            ];
        })->toArray();
    }

    /**
     * Get discount code performance metrics - optimized with batch queries.
     */
    private function getDiscountCodePerformance(?string $dateFrom, ?string $dateTo): array
    {
        // Get discount codes with redemption counts
        $query = DiscountCode::query();

        if ($dateFrom || $dateTo) {
            $query->whereHas('redemptions', function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) {
                    $q->where('created_at', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->where('created_at', '<=', $dateTo.' 23:59:59');
                }
            });
        }

        $discountCodes = $query->withCount('redemptions as total_redemptions')
            ->having('total_redemptions', '>', 0)
            ->orderByDesc('total_redemptions')
            ->limit(10)
            ->get();

        if ($discountCodes->isEmpty()) {
            return [];
        }

        $codeIds = $discountCodes->pluck('id')->toArray();

        // Get unique users per discount code in a single query
        $uniqueUserCounts = DB::table('discount_redemptions')
            ->whereIn('discount_code_id', $codeIds)
            ->groupBy('discount_code_id')
            ->select('discount_code_id', DB::raw('COUNT(DISTINCT user_id) as unique_users'))
            ->pluck('unique_users', 'discount_code_id')
            ->toArray();

        // Get active users per discount code in a single query
        $activeUserCounts = DB::table('discount_redemptions')
            ->join('subscriptions', 'discount_redemptions.user_id', '=', 'subscriptions.user_id')
            ->whereIn('discount_redemptions.discount_code_id', $codeIds)
            ->where('subscriptions.stripe_status', 'active')
            ->groupBy('discount_redemptions.discount_code_id')
            ->select('discount_redemptions.discount_code_id', DB::raw('COUNT(DISTINCT discount_redemptions.user_id) as active_users'))
            ->pluck('active_users', 'discount_code_id')
            ->toArray();

        // Get cancelled users per discount code in a single query
        $cancelledUserCounts = DB::table('discount_redemptions')
            ->join('subscriptions', 'discount_redemptions.user_id', '=', 'subscriptions.user_id')
            ->whereIn('discount_redemptions.discount_code_id', $codeIds)
            ->whereNotNull('subscriptions.ends_at')
            ->groupBy('discount_redemptions.discount_code_id')
            ->select('discount_redemptions.discount_code_id', DB::raw('COUNT(DISTINCT discount_redemptions.user_id) as cancelled_users'))
            ->pluck('cancelled_users', 'discount_code_id')
            ->toArray();

        return $discountCodes->map(function ($code) use ($uniqueUserCounts, $activeUserCounts, $cancelledUserCounts) {
            $uniqueUsers = $uniqueUserCounts[$code->id] ?? 0;
            $activeUsers = $activeUserCounts[$code->id] ?? 0;
            $cancelledUsers = $cancelledUserCounts[$code->id] ?? 0;

            $retentionRate = $uniqueUsers > 0
                ? round(($activeUsers / $uniqueUsers) * 100, 2)
                : 0;

            $cancellationRate = $uniqueUsers > 0
                ? round(($cancelledUsers / $uniqueUsers) * 100, 2)
                : 0;

            return [
                'id' => $code->id,
                'code' => $code->code,
                'description' => $code->description,
                'discount_type' => $code->discount_type,
                'discount_amount' => $code->discount_amount,
                'total_redemptions' => $code->total_redemptions,
                'unique_users' => $uniqueUsers,
                'active_users' => $activeUsers,
                'cancelled_users' => $cancelledUsers,
                'retention_rate' => $retentionRate,
                'cancellation_rate' => $cancellationRate,
            ];
        })->toArray();
    }

    /**
     * Get tier movement trends.
     */
    private function getTierTrends(array $userIds): array
    {
        $tierCounts = [
            'Free' => 0,
            'Gold' => 0,
            'Platinum' => 0,
        ];

        if (empty($userIds)) {
            return [
                'distribution' => $tierCounts,
                'total' => 0,
            ];
        }

        // Current tier distribution - single query
        $distribution = DB::table('subscriptions')
            ->join('stripe_products', 'subscriptions.stripe_price', '=', 'stripe_products.stripe_price_id')
            ->whereIn('subscriptions.user_id', $userIds)
            ->where('subscriptions.stripe_status', 'active')
            ->groupBy('stripe_products.tier')
            ->select('stripe_products.tier', DB::raw('COUNT(DISTINCT subscriptions.user_id) as count'))
            ->pluck('count', 'tier')
            ->toArray();

        foreach ($distribution as $tier => $count) {
            if (isset($tierCounts[$tier])) {
                $tierCounts[$tier] = $count;
            }
        }

        // Count users with no active subscription as Free
        $activeUserIds = DB::table('subscriptions')
            ->whereIn('user_id', $userIds)
            ->where('stripe_status', 'active')
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        $freeUsers = count(array_diff($userIds, $activeUserIds));
        $tierCounts['Free'] += $freeUsers;

        return [
            'distribution' => $tierCounts,
            'total' => array_sum($tierCounts),
        ];
    }
}
